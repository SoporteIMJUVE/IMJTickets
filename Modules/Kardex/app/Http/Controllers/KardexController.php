<?php

namespace Modules\Kardex\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\DepartamentoResolver;
use App\Support\IpAssigner;
use App\Support\KardexMovimiento;
use App\Support\NewAccountProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class KardexController extends Controller
{
    // ─── Vista principal: Equipos / Insumos / Resguardos / Impresoras ────────

    public function index()
    {
        $equipos = DB::table('inventario_equipos')
            ->leftJoin('users as resp', 'inventario_equipos.user_id',          '=', 'resp.id')
            ->leftJoin('users as usu',  'inventario_equipos.usuario_actual_id', '=', 'usu.id')
            ->select(
                'inventario_equipos.*',
                DB::raw("NULLIF(TRIM(COALESCE(resp.name,'') || ' ' || COALESCE(resp.apellido_paterno,'')), '') as empleado_nombre"),
                'resp.email as empleado_correo',
                DB::raw("NULLIF(TRIM(COALESCE(usu.name,'') || ' ' || COALESCE(usu.apellido_paterno,'')), '')  as usuario_nombre"),
                'usu.email as usuario_correo',
            )
            ->orderBy('inventario_equipos.tipo')
            ->orderBy('inventario_equipos.consecutivo')
            ->get();

        // Resguardos: equipos institucionales = los que tienen al menos un evento
        // 'Entrada' en movimientos_equipos (con o sin PDF adjunto).
        // Los equipos personales (sin Entrada) quedan excluidos de esta tab.
        $idsInstitucionales = DB::table('movimientos_equipos')
            ->where('tipo_activo', 'equipo')
            ->where('tipo_evento', 'Entrada')
            ->pluck('activo_id')
            ->flip();

        $resguardos = $equipos->filter(fn ($e) => $idsInstitucionales->has($e->id))->values();

        $insumos = DB::table('insumos')->orderBy('nombre_insumo')->get();

        $impresoras = DB::table('impresoras')
            ->leftJoin('users', 'impresoras.user_id', '=', 'users.id')
            ->select(
                'impresoras.*',
                'users.name as responsable_nombre',
                'users.email as responsable_correo'
            )
            ->orderBy('impresoras.area')
            ->get();

        return view('kardex::index', [
            'equipos'                  => $equipos,
            'resguardos'               => $resguardos,
            'insumos'                  => collect(),
            'impresoras'               => $impresoras,
            'laptopsAsignadas'         => $equipos->where('tipo', 'Laptop')->whereNotNull('user_id')->count(),
            'pcAvanzadasAsignadas'     => $equipos->where('tipo', 'PC Avanzada')->whereNotNull('user_id')->count(),
            'pcEspecializadasAsignadas'=> $equipos->where('tipo', 'PC Especializada')->whereNotNull('user_id')->count(),
            'impresorasAsignadas'      => $impresoras->whereNotNull('user_id')->count(),
            'telefonosAsignados'       => $equipos->where('tipo', 'Telefono')->whereNotNull('user_id')->count(),
        ]);
    }

    // ─── POST: cambia el estado de un equipo ─────────────────────────────────

    public function cambiarEstadoEquipo(Request $request, $id)
    {
        $estado = $request->estado ?: null;

        // Equipos personales (sin evento 'Entrada') solo admiten Baja
        if (in_array($estado, ['almacen', 'mantenimiento'], true)) {
            $tieneResguardo = \Schema::hasTable('movimientos_equipos') && DB::table('movimientos_equipos')
                ->where('activo_id', $id)
                ->where('tipo_activo', 'equipo')
                ->where('tipo_evento', 'Entrada')
                ->exists();

            if (!$tieneResguardo) {
                return response()->json([
                    'error' => 'Equipo personal (sin resguardo): solo puede cambiar a Baja. Almacén y Mantenimiento requieren resguardo del IMJUVE.',
                ], 422);
            }
        }

        $equipo = DB::table('inventario_equipos')
            ->leftJoin('users', 'inventario_equipos.user_id', '=', 'users.id')
            ->select(
                'inventario_equipos.user_id',
                'inventario_equipos.estado as estado_actual',
                'inventario_equipos.ipv4',
                'inventario_equipos.cpu_serie',
                'inventario_equipos.area',
                DB::raw("NULLIF(TRIM(COALESCE(users.name,'') || ' ' || COALESCE(users.apellido_paterno,'')), '') as responsable_nombre")
            )
            ->where('inventario_equipos.id', $id)
            ->first();

        if ($estado === 'almacen') {
            DB::table('inventario_equipos')->where('id', $id)->update([
                'estado'            => null,
                'user_id'           => null,
                'usuario_actual_id' => null,
                'updated_at'        => now(),
            ]);

            KardexMovimiento::registrar(
                tipo_activo:   'equipo',
                activo_id:     (int) $id,
                tipo_evento:   'Almacén',
                origen:        $equipo->responsable_nombre ?? 'Sin responsable',
                destino:       'Almacén',
                user_from_id:  $equipo->user_id,
                estado_equipo: 'Almacén',
            );

            return response()->json(['ok' => true]);
        }

        if ($estado === 'baja') {
            DB::table('inventario_equipos')->where('id', $id)->update([
                'estado'            => 'baja',
                'user_id'           => null,
                'usuario_actual_id' => null,
                'nombre_usuario'    => null,
                'updated_at'        => now(),
            ]);

            KardexMovimiento::registrar(
                tipo_activo:   'equipo',
                activo_id:     (int) $id,
                tipo_evento:   'Baja',
                origen:        $equipo->area ?? 'Sin área',
                destino:       'Proveedor',
                user_from_id:  $equipo->user_id,
                estado_equipo: 'Baja',
            );
        } else {
            DB::table('inventario_equipos')->where('id', $id)->update([
                'estado'     => $estado,
                'updated_at' => now(),
            ]);

            KardexMovimiento::registrar(
                tipo_activo:   'equipo',
                activo_id:     (int) $id,
                tipo_evento:   'Mantenimiento',
                origen:        $equipo->responsable_nombre ?? 'Sin responsable',
                user_from_id:  $equipo->user_id,
                estado_equipo: 'Mantenimiento',
            );
        }

        if (in_array($estado, ['mantenimiento', 'baja'], true)) {
            if ($equipo->ipv4) {
                KardexMovimiento::registrar(
                    tipo_activo:   'equipo',
                    activo_id:     (int) $id,
                    tipo_evento:   'Liberación IP',
                    origen:        $equipo->cpu_serie ?? '—',
                    destino:       'Sin equipo',
                    estado_equipo: 'Libre',
                    notas:         "IP: {$equipo->ipv4}",
                );
            }
            IpAssigner::liberarEquipo((int) $id);
        }

        return response()->json(['ok' => true]);
    }

    // ─── POST: cambia el estado de una impresora ─────────────────────────────

    public function cambiarEstadoImpresora(Request $request, $id)
    {
        $estado = $request->estado ?: null;

        $impresora = DB::table('impresoras')
            ->leftJoin('users', 'impresoras.user_id', '=', 'users.id')
            ->select(
                'impresoras.user_id',
                DB::raw("NULLIF(TRIM(COALESCE(users.name,'') || ' ' || COALESCE(users.apellido_paterno,'')), '') as responsable_nombre")
            )
            ->where('impresoras.id_impresora', $id)
            ->first();

        if ($estado === 'almacen') {
            DB::table('impresoras')->where('id_impresora', $id)->update([
                'estado'     => null,
                'user_id'    => null,
                'updated_at' => now(),
            ]);

            KardexMovimiento::registrar(
                tipo_activo:  'impresora',
                activo_id:    (int) $id,
                tipo_evento:  'Almacén',
                origen:       $impresora->responsable_nombre ?? 'Sin responsable',
                destino:      'Almacén',
                user_from_id: $impresora->user_id,
                estado_equipo:'Almacén',
            );

            return response()->json(['ok' => true]);
        }

        DB::table('impresoras')->where('id_impresora', $id)->update([
            'estado'     => $estado,
            'updated_at' => now(),
        ]);

        $tipoEvento   = match($estado) {
            'mantenimiento' => 'Mantenimiento',
            'baja'          => 'Baja',
            default         => 'Almacén',
        };
        $estadoEquipo = match($estado) {
            'mantenimiento' => 'Mantenimiento',
            'baja'          => 'Baja',
            default         => $impresora->user_id ? 'Asignado' : 'Almacén',
        };

        KardexMovimiento::registrar(
            tipo_activo:  'impresora',
            activo_id:    (int) $id,
            tipo_evento:  $tipoEvento,
            origen:       $impresora->responsable_nombre ?? 'Sin responsable',
            user_from_id: $impresora->user_id,
            estado_equipo:$estadoEquipo,
        );

        return response()->json(['ok' => true]);
    }

    // ─── POST: cambia el usuario actual del equipo (quien lo usa físicamente) ──

    public function cambiarUsuarioEquipo(Request $request, $id)
    {
        $request->validate(['usuario_id' => 'nullable|exists:users,id']);
        $nuevoUsuarioId = $request->usuario_id ? (int) $request->usuario_id : null;

        $equipo = DB::table('inventario_equipos')
            ->leftJoin('users as usu', 'inventario_equipos.usuario_actual_id', '=', 'usu.id')
            ->select(
                'inventario_equipos.usuario_actual_id',
                DB::raw("NULLIF(TRIM(COALESCE(usu.name,'') || ' ' || COALESCE(usu.apellido_paterno,'')), '') as usuario_anterior_nombre")
            )
            ->where('inventario_equipos.id', $id)
            ->first();

        DB::table('inventario_equipos')->where('id', $id)->update([
            'usuario_actual_id' => $nuevoUsuarioId,
            'updated_at'        => now(),
        ]);

        // Nombre del nuevo usuario para el historial
        $nuevoNombre = null;
        if ($nuevoUsuarioId) {
            $nuevoNombre = DB::table('users')->where('id', $nuevoUsuarioId)
                ->selectRaw("NULLIF(TRIM(COALESCE(name,'') || ' ' || COALESCE(apellido_paterno,'')), '') as nombre")
                ->value('nombre');
        }

        KardexMovimiento::registrar(
            tipo_activo:   'equipo',
            activo_id:     (int) $id,
            tipo_evento:   'Reasignación',
            origen:        $equipo->usuario_anterior_nombre ?? 'Sin usuario',
            destino:       $nuevoNombre ?? 'Sin usuario',
            user_from_id:  $equipo->usuario_actual_id,
            user_to_id:    $nuevoUsuarioId,
            notas:         'Cambio de usuario físico del equipo.',
        );

        return response()->json([
            'ok'            => true,
            'usuario_nombre'=> $nuevoNombre,
            'usuario_id'    => $nuevoUsuarioId,
        ]);
    }

    // ─── Vista: formulario de carga ──────────────────────────────────────────

    public function subirResguardo()
    {
        return view('kardex::resguardo-subir');
    }

    // ─── POST: extrae texto del PDF y muestra previsualización ───────────────

    public function extraerResguardo(Request $request)
    {
        $request->validate([
            'pdf' => 'required|file|mimes:pdf|max:10240',
        ], [
            'pdf.required' => 'Selecciona un archivo PDF.',
            'pdf.mimes'    => 'El archivo debe ser un PDF.',
            'pdf.max'      => 'El archivo no debe superar 10 MB.',
        ]);

        $file = $request->file('pdf');

        $parser = new Parser();
        $pdf    = $parser->parseFile($file->getPathname());
        $texto  = $pdf->getText();

        $esNativo = mb_strlen(trim($texto)) > 80;

        // Si tiene texto: extraer campos
        // Si es imagen (sin texto): datos vacíos, formulario manual
        $datos = $esNativo
            ? $this->extraerCampos($texto)
            : array_fill_keys(['nombre_usuario','tipo','cpu_marca','cpu_modelo','cpu_serie',
                               'consecutivo','num_inventario','area','observaciones'], null);

        $hayDatos = $esNativo && count(array_filter($datos)) > 0;

        // Solo buscar candidatos si extrajimos un nombre
        $candidatos = collect();
        if ($hayDatos && $datos['nombre_usuario']) {
            $partes   = preg_split('/\s+/', trim($datos['nombre_usuario']));
            $nombre   = $partes[0] ?? '';
            $apellido = $partes[1] ?? '';
            $candidatos = DB::table('users')
                ->leftJoin('departamentos', 'users.id_departamento', '=', 'departamentos.id_departamento')
                ->where('users.activo', true)
                ->where(function ($q) use ($nombre, $apellido) {
                    $q->where('users.name', 'like', "%{$nombre}%")
                      ->orWhere('users.apellido_paterno', 'like', "%{$apellido}%")
                      ->orWhere('users.apellido_materno', 'like', "%{$apellido}%");
                })
                ->select(
                    'users.id as id_empleado',
                    'users.name as nombre',
                    'users.apellido_paterno',
                    'users.apellido_materno',
                    'users.email as correo',
                    'departamentos.nombre as departamento',
                    DB::raw("(SELECT ip.ip FROM inventario_equipos ie
                              JOIN inventario_ips_completo ip ON ip.id = ie.ip_id
                              WHERE ie.user_id = users.id AND ie.ip_id IS NOT NULL
                              ORDER BY ie.updated_at DESC LIMIT 1) as ip_actual")
                )
                ->limit(5)
                ->get();
        }

        // Siempre guardar el PDF (también para escaneados)
        $tmpPath = $file->store('resguardos_tmp', 'local');

        session([
            'resguardo.datos'      => $datos,
            'resguardo.textoRaw'   => $texto,
            'resguardo.candidatos' => $candidatos,
            'resguardo.tmpPdf'     => $tmpPath,
            'resguardo.esNativo'   => $esNativo,
        ]);

        return redirect()->route('kardex.resguardo.preview');
    }

    // ─── GET: muestra previsualización (PRG) ─────────────────────────────────

    public function mostrarPreview()
    {
        if (!session()->has('resguardo.datos')) {
            return redirect()->route('kardex.resguardo.subir')
                ->withErrors(['pdf' => 'La sesión expiró. Vuelve a subir el PDF.']);
        }

        $directores = DB::table('users')
            ->leftJoin('departamentos', 'users.id_departamento', '=', 'departamentos.id_departamento')
            ->where('users.activo', true)
            ->where('users.puesto', 'like', '%Director%')
            ->select(
                'users.id as id_empleado',
                'users.name as nombre',
                'users.apellido_paterno',
                'users.email as correo',
                'departamentos.nombre as departamento',
                DB::raw("(SELECT ip.ip FROM inventario_equipos ie
                          JOIN inventario_ips_completo ip ON ip.id = ie.ip_id
                          WHERE ie.user_id = users.id AND ie.ip_id IS NOT NULL
                          ORDER BY ie.updated_at DESC LIMIT 1) as ip_actual")
            )
            ->orderBy('users.name')
            ->get();

        $datos = session('resguardo.datos');

        // Si la série ya existe en inventario, el formulario detecta automáticamente
        // si la operación será Reasignación (diferente responsable) o Renovación (mismo responsable).
        $equipoExistente = null;
        if (!empty($datos['cpu_serie'])) {
            $equipoExistente = DB::table('inventario_equipos as eq')
                ->leftJoin('users as resp', 'eq.user_id', '=', 'resp.id')
                ->where('eq.cpu_serie', $datos['cpu_serie'])
                ->select(
                    'eq.id', 'eq.user_id', 'eq.tipo', 'eq.cpu_marca', 'eq.cpu_modelo', 'eq.area', 'eq.ipv4',
                    DB::raw("NULLIF(TRIM(COALESCE(resp.name,'') || ' ' || COALESCE(resp.apellido_paterno,'')), '') as responsable_nombre"),
                    'resp.email as responsable_correo'
                )
                ->first();
        }

        return view('kardex::resguardo-preview', [
            'datos'           => $datos,
            'textoRaw'        => session('resguardo.textoRaw'),
            'candidatos'      => collect(session('resguardo.candidatos', []))->map(fn($e) => (object) $e),
            'directores'      => $directores,
            'tmpPdf'          => session('resguardo.tmpPdf'),
            'esNativo'        => session('resguardo.esNativo', true),
            'equipoExistente' => $equipoExistente,
        ]);
    }

    // ─── POST: confirma y guarda en base de datos ─────────────────────────────

    public function guardarResguardo(Request $request)
    {
        $request->validate([
            'tipo'          => 'required|in:Laptop,PC Avanzada,PC Especializada',
            'cpu_serie'     => 'required|string|max:100',
            'area'          => 'required|string|max:200',
            'tmp_pdf'       => 'required|string',
            'id_empleado'   => 'required|string',
            'nombre_pdf_detectado' => 'nullable|string|max:200',
            'ipv4'          => 'nullable|ip',
            'nuevo_nombre'           => 'nullable|required_if:id_empleado,__nuevo__|string|max:80',
            'nuevo_apellido_paterno' => 'nullable|required_if:id_empleado,__nuevo__|string|max:80',
            'nuevo_apellido_materno' => 'nullable|string|max:80',
            'nuevo_correo'           => 'nullable|email|max:120|unique:users,email',
            'nuevo_puesto'           => 'nullable|string|max:120',
        ], [
            'tipo.required'                      => 'El tipo de equipo es obligatorio.',
            'cpu_serie.required'                 => 'El número de serie del equipo es obligatorio.',
            'area.required'                      => 'El área es obligatoria.',
            'id_empleado.required'               => 'Todo resguardo debe tener un responsable asignado.',
            'nuevo_nombre.required_if'           => 'El nombre es obligatorio para crear una persona nueva.',
            'nuevo_apellido_paterno.required_if' => 'El apellido paterno es obligatorio para crear una persona nueva.',
            'nuevo_correo.unique'                => 'Ese correo ya está registrado en otra cuenta.',
        ]);

        if ($request->id_empleado && $request->id_empleado !== '__nuevo__'
            && !DB::table('users')->where('id', $request->id_empleado)->exists()) {
            return redirect()->route('kardex.resguardo.preview')->withInput()->withErrors([
                'id_empleado' => 'La persona seleccionada ya no existe.',
            ]);
        }

        // Buscar si la série ya existe — determina el tipo de operación Kardex.
        $existente = DB::table('inventario_equipos as eq')
            ->leftJoin('users as resp', 'eq.user_id', '=', 'resp.id')
            ->where('eq.cpu_serie', $request->cpu_serie)
            ->select(
                'eq.*',
                DB::raw("NULLIF(TRIM(COALESCE(resp.name,'') || ' ' || COALESCE(resp.apellido_paterno,'')), '') as responsable_nombre")
            )
            ->first();

        // Responsable: id existente, persona nueva creada aquí mismo, o ninguno.
        $userId = null;
        if ($request->id_empleado === '__nuevo__') {
            $correoNuevo = $request->nuevo_correo ?: NewAccountProvisioner::placeholderEmail(Str::random(8));

            // El área capturada en el resguardo es el departamento donde va
            // a trabajar esta persona nueva — se resuelve/crea en departamentos.
            $idDepartamento = $request->area ? DepartamentoResolver::resolveId($request->area) : null;

            $userId = DB::table('users')->insertGetId([
                'name'             => trim($request->nuevo_nombre),
                'apellido_paterno' => trim($request->nuevo_apellido_paterno),
                'apellido_materno' => trim($request->nuevo_apellido_materno ?? ''),
                'email'            => $correoNuevo,
                'password'         => NewAccountProvisioner::tempPasswordHash(),
                'role'             => 'user',
                'puesto'           => $request->nuevo_puesto ?: null,
                'id_departamento'  => $idDepartamento,
                'activo'           => true,
                'fecha_alta'       => now(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);
        } elseif ($request->id_empleado) {
            $userId = (int) $request->id_empleado;
        }

        $nombreEmpleado = DB::table('users')->where('id', $userId)
            ->selectRaw("NULLIF(TRIM(COALESCE(name,'') || ' ' || COALESCE(apellido_paterno,'')), '') as nombre")
            ->value('nombre') ?? 'Empleado';

        // Helper para mover PDF de tmp a almacenamiento permanente
        $moverPdf = function (int $idEquipo) use ($request): ?string {
            if ($request->tmp_pdf && Storage::disk('local')->exists($request->tmp_pdf)) {
                $path = "resguardos/{$idEquipo}.pdf";
                Storage::disk('local')->move($request->tmp_pdf, $path);
                DB::table('inventario_equipos')->where('id', $idEquipo)->update(['pdf_resguardo' => $path]);
                return $path;
            }
            return null;
        };

        // ── CASO A: Equipo ya existe — Reasignación o Renovación ─────────────
        if ($existente) {
            $mismoResponsable = $existente->user_id && (int) $existente->user_id === $userId;

            // Actualizar metadata del PDF nuevo (periféricos, área, etc.)
            DB::table('inventario_equipos')->where('id', $existente->id)->update(array_filter([
                'tipo'           => $request->tipo,
                'area'           => $request->area           ?: $existente->area,
                'cpu_marca'      => $request->cpu_marca      ?: $existente->cpu_marca,
                'cpu_modelo'     => $request->cpu_modelo     ?: $existente->cpu_modelo,
                'cargador_serie' => $request->cargador_serie ?: $existente->cargador_serie,
                'docking_marca'  => $request->docking_marca  ?: $existente->docking_marca,
                'docking_serie'  => $request->docking_serie  ?: $existente->docking_serie,
                'monitor_marca'  => $request->monitor_marca  ?: $existente->monitor_marca,
                'monitor_serie'  => $request->monitor_serie  ?: $existente->monitor_serie,
                'teclado_serie'  => $request->teclado_serie  ?: $existente->teclado_serie,
                'mouse_serie'    => $request->mouse_serie    ?: $existente->mouse_serie,
                'nobreak_marca'  => $request->nobreak_marca  ?: $existente->nobreak_marca,
                'nobreak_serie'  => $request->nobreak_serie  ?: $existente->nobreak_serie,
                'observaciones'  => $request->observaciones  ?: $existente->observaciones,
                'updated_at'     => now(),
            ], fn($v) => $v !== null));

            $moverPdf($existente->id);

            // ── IP: asignar o cambiar si el admin proporcionó una nueva ──────
            $msgIp = '';
            $ipNueva = $request->ipv4 ? trim($request->ipv4) : null;
            if ($ipNueva && $ipNueva !== ($existente->ipv4 ?? '')) {
                $ipId    = IpAssigner::resolveId($ipNueva);
                $ocupante = IpAssigner::findOccupant($ipId);

                if ($ocupante && (int) $ocupante->id !== (int) $existente->id) {
                    // La IP está en un dispositivo distinto al que se está guardando.
                    if ($ocupante->tabla === 'inventario_equipos' && (int) $ocupante->user_id !== $userId) {
                        // Diferente equipo, diferente propietario → bloquear
                        return redirect()->route('kardex.resguardo.preview')->withInput()->withErrors([
                            'ipv4' => "La IP {$ipNueva} ya está asignada a otro equipo (distinto propietario). Libérala primero desde el panel Network.",
                        ]);
                    }
                    // Mismo propietario (u ocupante es impresora) → switcheo implícito:
                    // se desvincula la IP del dispositivo anterior sin pasar por 'Libre',
                    // porque inmediatamente se reasigna abajo.
                    $pkAnterior      = $ocupante->tabla === 'inventario_equipos' ? 'id' : 'id_impresora';
                    $campoIpAnterior = $ocupante->tabla === 'inventario_equipos' ? 'ipv4' : 'ip_address';
                    DB::table($ocupante->tabla)->where($pkAnterior, $ocupante->id)->update([
                        'ip_id'          => null,
                        $campoIpAnterior => null,
                        'updated_at'     => now(),
                    ]);
                    KardexMovimiento::registrar(
                        tipo_activo:   $ocupante->tabla === 'inventario_equipos' ? 'equipo' : 'impresora',
                        activo_id:     (int) $ocupante->id,
                        tipo_evento:   'Liberación IP',
                        origen:        $existente->cpu_serie,
                        destino:       $existente->cpu_serie,
                        estado_equipo: 'Libre',
                        notas:         "IP: {$ipNueva} — liberada por reasignación de resguardo",
                    );
                }

                // Registrar evento según si ya tenía IP o no
                $tipoEventoIp = $existente->ipv4 ? 'Cambio IP' : 'Asignación IP';
                $notaIp = $existente->ipv4
                    ? "IP: {$existente->ipv4} → {$ipNueva}"
                    : "IP: {$ipNueva}";

                DB::table('inventario_equipos')->where('id', $existente->id)->update([
                    'ip_id'      => $ipId,
                    'ipv4'       => $ipNueva,
                    'updated_at' => now(),
                ]);

                KardexMovimiento::registrar(
                    tipo_activo:   'equipo',
                    activo_id:     $existente->id,
                    tipo_evento:   $tipoEventoIp,
                    origen:        $existente->cpu_serie,
                    destino:       $existente->cpu_serie,
                    estado_equipo: 'Ocupada',
                    notas:         $notaIp,
                );

                $accionIp = $tipoEventoIp === 'Cambio IP' ? 'actualizada' : 'asignada';
                $msgIp = " IP {$accionIp}: {$ipNueva}.";
            }

            if (!$mismoResponsable) {
                // ── Reasignación: nuevo responsable formal ────────────────
                DB::table('inventario_equipos')->where('id', $existente->id)->update([
                    'user_id'           => $userId,
                    'usuario_actual_id' => $userId,
                    'updated_at'        => now(),
                ]);

                KardexMovimiento::registrar(
                    tipo_activo:   'equipo',
                    activo_id:     $existente->id,
                    tipo_evento:   'Reasignación',
                    origen:        $existente->responsable_nombre ?? 'Sin responsable',
                    destino:       $nombreEmpleado,
                    user_from_id:  $existente->user_id,
                    user_to_id:    $userId,
                    ticket_ref:    $request->ticket_ref ?: null,
                    estado_equipo: 'Asignado',
                    notas:         'Cambio de responsable por nuevo resguardo PDF.',
                );

                return redirect()->route('kardex.index')
                    ->with('success', "Responsable de {$request->cpu_serie} actualizado a {$nombreEmpleado}. Reasignación registrada en Kardex.{$msgIp}");
            }

            // ── Renovación: mismo responsable, solo actualiza PDF (+ IP si hubo) ─
            return redirect()->route('kardex.index')
                ->with('success', "PDF de resguardo renovado para {$request->cpu_serie} (ID {$existente->id}).{$msgIp}");
        }

        // ── CASO B: Equipo nuevo — Entrada + Asignación ───────────────────────

        // Resolver la IP contra el registro maestro. Si ya la tiene asignada
        // ESTA MISMA persona en otro equipo, se libera de ahí (switcheo). Si
        // es de alguien más (u otro dispositivo), se bloquea.
        $ipId = null;
        $switcheo = false;
        if ($request->ipv4) {
            $ipId = IpAssigner::resolveId($request->ipv4);
            $ocupante = IpAssigner::findOccupant($ipId);

            if ($ocupante && (int) $ocupante->user_id !== $userId) {
                return redirect()->route('kardex.resguardo.preview')->withInput()->withErrors([
                    'ipv4' => "La IP {$request->ipv4} ya está asignada a otro dispositivo.",
                ]);
            }

            if ($ocupante && $ocupante->tabla === 'inventario_equipos') {
                DB::table('inventario_equipos')->where('id', $ocupante->id)->update([
                    'ip_id'      => null,
                    'ipv4'       => null,
                    'updated_at' => now(),
                ]);
                $switcheo = true;
            }
        }

        $idEquipo = DB::table('inventario_equipos')->insertGetId([
            'tipo'           => $request->tipo,
            'consecutivo'    => $request->consecutivo ?: null,
            'num_inventario' => $request->num_inventario ?: null,
            'nombre_equipo'  => trim($request->cpu_marca . ' ' . $request->cpu_modelo) ?: null,
            'nombre_usuario' => $request->nombre_usuario ?: null,
            'area'           => $request->area ?: null,
            'cpu_marca'      => $request->cpu_marca ?: null,
            'cpu_modelo'     => $request->cpu_modelo ?: null,
            'cpu_serie'      => $request->cpu_serie,
            'cargador_serie' => $request->cargador_serie ?: null,
            'docking_marca'  => $request->docking_marca ?: null,
            'docking_modelo' => $request->docking_modelo ?: null,
            'docking_serie'  => $request->docking_serie ?: null,
            'monitor_marca'  => $request->monitor_marca ?: null,
            'monitor_modelo' => $request->monitor_modelo ?: null,
            'monitor_serie'  => $request->monitor_serie ?: null,
            'teclado_serie'  => $request->teclado_serie ?: null,
            'mouse_serie'    => $request->mouse_serie ?: null,
            'nobreak_marca'  => $request->nobreak_marca ?: null,
            'nobreak_modelo' => $request->nobreak_modelo ?: null,
            'nobreak_serie'  => $request->nobreak_serie ?: null,
            'ipv4'           => $request->ipv4 ?: null,
            'ip_id'          => $ipId,
            'observaciones'  => $request->observaciones ?: null,
            'user_id'           => $userId,
            'usuario_actual_id' => $userId,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        $moverPdf($idEquipo);

        // Ciclo de vida: ENTRADA → ASIGNACIÓN
        KardexMovimiento::registrar(
            tipo_activo:   'equipo',
            activo_id:     $idEquipo,
            tipo_evento:   'Entrada',
            origen:        'Proveedor',
            destino:       'Subdirección de Sistemas',
            estado_equipo: 'Almacén',
        );

        // Detectar si el nombre del PDF no coincide con el responsable asignado
        $notaAsignacion = $request->observaciones;
        $nombrePdf = trim($request->nombre_pdf_detectado ?? '');
        if ($nombrePdf) {
            $palabrasPdf = array_filter(explode(' ', mb_strtolower($nombrePdf)), fn($p) => mb_strlen($p) > 2);
            $nombreNorm  = mb_strtolower($nombreEmpleado);
            $coincide    = !empty($palabrasPdf) && collect($palabrasPdf)->some(fn($p) => str_contains($nombreNorm, $p));
            if (!$coincide) {
                $aviso = "⚠️ PDF menciona \"{$nombrePdf}\" — asignado a \"{$nombreEmpleado}\" por " . (\Auth::user()?->email ?? 'admin');
                $notaAsignacion = $aviso . ($notaAsignacion ? ' | ' . $notaAsignacion : '');
            }
        }

        KardexMovimiento::registrar(
            tipo_activo:   'equipo',
            activo_id:     $idEquipo,
            tipo_evento:   'Asignación',
            origen:        'Subdirección de Sistemas',
            destino:       $nombreEmpleado,
            user_to_id:    $userId,
            ticket_ref:    $request->ticket_ref ?: null,
            estado_equipo: 'Asignado',
            notas:         $notaAsignacion,
        );

        // ── Eventos de IP ────────────────────────────────────────────────────
        if ($request->ipv4) {
            $serieAnterior = null;
            if ($switcheo) {
                $serieAnterior = DB::table('inventario_equipos')->where('id', $ocupante->id)->value('cpu_serie') ?? '—';
                KardexMovimiento::registrar(
                    tipo_activo:   'equipo',
                    activo_id:     $ocupante->id,
                    tipo_evento:   'Liberación IP',
                    origen:        $serieAnterior,
                    destino:       $request->cpu_serie,
                    estado_equipo: 'Libre',
                    notas:         "IP: {$request->ipv4} — liberada por switcheo",
                );
            }
            KardexMovimiento::registrar(
                tipo_activo:   'equipo',
                activo_id:     $idEquipo,
                tipo_evento:   'Asignación IP',
                origen:        $serieAnterior ?? 'Sin equipo',
                destino:       $request->cpu_serie,
                estado_equipo: 'Ocupada',
                notas:         "IP: {$request->ipv4}",
            );
        }

        $mensaje = "Equipo registrado (ID {$idEquipo}). PDF guardado en el sistema.";
        if ($switcheo) {
            $mensaje .= ' Se liberó la IP de su equipo anterior y se reasignó a este (switcheo).';
        }

        return redirect()->route('kardex.index')->with('success', $mensaje);
    }

    // ─── Privado: extrae campos del texto del PDF ─────────────────────────────

    private function extraerCampos(string $texto): array
    {
        $find = function (string $pattern) use ($texto): ?string {
            return preg_match($pattern, $texto, $m) ? trim($m[1]) : null;
        };

        // Nombre del usuario — "NOMBRE DEL USUARIO: ..."
        $nombreUsuario = $find('/NOMBRE DEL USUARIO\s*[:\|]?\s*(.+)/i');

        // Tipo de equipo
        $tipo = $find('/(Laptop|PC Avanzada|PC Especializada)/i');
        if ($tipo) {
            $mapa = ['laptop' => 'Laptop', 'pc avanzada' => 'PC Avanzada', 'pc especializada' => 'PC Especializada'];
            $tipo = $mapa[strtolower($tipo)] ?? $tipo;
        }

        // Iniciales para fila de tabla
        $serie      = null;
        $inventario = null;
        $marca      = null;
        $modelo     = null;

        // Estrategia 1: fila de tabla con columnas separadas por tabulaciones o 2+ espacios.
        // Acepta \t+, 2+ espacios o mezcla — el modelo puede tener un espacio interno.
        // Formato típico: Laptop    DELL    Latitude 3420    33KGW93    65
        $sepCol = '(?:\t+|[ ]{2,})';   // separador de columna: tab(s) o ≥2 espacios
        if (preg_match(
            '/^(Laptop|PC\s+Avanzada|PC\s+Especializada)' . $sepCol .
            '(\S+)' . $sepCol .
            '(.+?)' . $sepCol .
            '([A-Z0-9]{4,20})' . $sepCol .
            '(\d+)\s*$/mi',
            $texto, $m
        )) {
            $marca      = trim($m[2]);
            $modelo     = trim($m[3]);
            $serie      = trim($m[4]);
            $inventario = trim($m[5]);
        }

        // Estrategia 2: campo explícito "NO. SERIE: xxx" (resguardos formales del IMJUVE).
        // Solo se activa si la fila de tabla no capturó la serie.
        // Se salta si el siguiente token es "INVENTARIO" (encabezado de columna).
        if (!$serie) {
            if (preg_match('/NO[\.\s]*SERIE\s*[:\|]?\s*([A-Z0-9\-]{4,})/i', $texto, $m)) {
                $candidato = trim($m[1]);
                if (strtoupper($candidato) !== 'INVENTARIO') {
                    $serie = $candidato;
                }
            }
        }

        // Número de inventario: solo desde la fila de tabla (Strategy 1).
        // No se extrae del texto libre para evitar capturar números de inventario
        // mencionados en contextos como "equipo inventario 79 dañado".
        // Si la tabla no lo capturó queda null y el admin lo llena a mano.

        // Marca / modelo si no vinieron de la tabla
        if (!$marca && $tipo) {
            $tipoEsc = preg_quote($tipo, '/');
            if (preg_match('/' . $tipoEsc . '\s+([\w]+)\s+([\w][\w\s\-]+?)(?:\s{2,}|\t)/i', $texto, $m)) {
                $marca  = trim($m[1]);
                $modelo = trim($m[2]);
            }
        }

        // Área — "ÁREA: ..."
        $area = $find('/ÁREA\s*[:\|]?\s*(.+)/i');

        // Observaciones — texto después del encabezado OBSERVACIONES
        $observaciones = null;
        if (preg_match('/OBSERVACIONES\s*\n+(.+?)(?:\n{2,}|¿El equipo|$)/si', $texto, $m)) {
            $observaciones = trim($m[1]);
        }

        return [
            'nombre_usuario' => $nombreUsuario,
            'tipo'           => $tipo,
            'cpu_marca'      => $marca,
            'cpu_modelo'     => $modelo,
            'cpu_serie'      => $serie,
            'consecutivo'    => $inventario ? (int) $inventario : null,
            'num_inventario' => $inventario,
            'area'           => $area,
            'observaciones'  => $observaciones,
        ];
    }

    // ─── Público: sugiere primera IP libre del rango del área ────────────────

    public function sugerirIp(Request $request)
    {
        $area = $request->query('area');
        if (!$area) return response()->json(['ip' => null]);

        $rango = DB::table('cat_rangos_ips')
            ->where('area_nombre', 'like', "%{$area}%")
            ->first();

        if (!$rango) return response()->json(['ip' => null, 'mensaje' => 'Sin rango para esa área']);

        // Fuente de verdad: el estatus del registro maestro, no lo que haya
        // escrito cada equipo. Una IP sin fila registrada se sigue tratando
        // como libre.
        $usadas = DB::table('inventario_ips_completo')
            ->where('estatus', '<>', 'Libre')
            ->pluck('ip')
            ->flip()
            ->all();

        $inicio = ip2long($rango->ip_inicial);
        $fin    = ip2long($rango->ip_final);

        for ($n = $inicio; $n <= $fin; $n++) {
            $candidata = long2ip($n);
            if (!isset($usadas[$candidata])) {
                return response()->json(['ip' => $candidata]);
            }
        }

        return response()->json(['ip' => null, 'mensaje' => 'Rango sin IPs disponibles']);
    }

    // ─── Importación masiva de equipos (solo equipos sin resguardo) ───────────

    public function validarImport(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $path     = $request->file('archivo')->store('temp_imports', 'local');
        $fullPath = Storage::disk('local')->path($path);

        try {
            $importer  = new \App\Imports\EquiposImporter();
            $resultado = $importer->process($fullPath, dryRun: true);

            session(['import_temp_path' => $path, 'import_temp_ts' => now()->timestamp]);

            return response()->json($resultado);
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($path);
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function aplicarImport(Request $request)
    {
        $path = session('import_temp_path');
        $ts   = (int) session('import_temp_ts', 0);

        if (!$path || (now()->timestamp - $ts) > 1800) {
            return response()->json(['error' => 'Sesión de importación expirada (30 min). Vuelve a subir el archivo.'], 422);
        }

        $fullPath = Storage::disk('local')->path($path);

        if (!file_exists($fullPath)) {
            return response()->json(['error' => 'El archivo temporal ya no existe. Vuelve a subir.'], 422);
        }

        try {
            $importer  = new \App\Imports\EquiposImporter();
            $resultado = $importer->process($fullPath, dryRun: false);

            Storage::disk('local')->delete($path);
            session()->forget(['import_temp_path', 'import_temp_ts']);

            return response()->json($resultado);
        } catch (\Throwable $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    // ─── Licencias ─────────────────────────────────────────────────────────────

    public function licenciasJson()
    {
        $licencias = DB::table('licencias')->orderBy('correo')->get();

        $nUsuarios = DB::table('licencia_users')
            ->selectRaw('licencia_id, COUNT(*) as total')
            ->groupBy('licencia_id')
            ->pluck('total', 'licencia_id');

        $nEquipos = DB::table('licencia_equipos')
            ->selectRaw('licencia_id, COUNT(*) as total')
            ->groupBy('licencia_id')
            ->pluck('total', 'licencia_id');

        return response()->json($licencias->map(function ($lic) use ($nUsuarios, $nEquipos) {
            $lic->n_usuarios = (int) ($nUsuarios[$lic->id] ?? 0);
            $lic->n_equipos  = (int) ($nEquipos[$lic->id] ?? 0);
            return $lic;
        }));
    }

    public function licenciaDetalle($id)
    {
        $lic = DB::table('licencias')->where('id', $id)->first();
        abort_if(!$lic, 404);

        $titulares = DB::table('licencia_users')
            ->join('users', 'licencia_users.user_id', '=', 'users.id')
            ->where('licencia_users.licencia_id', $id)
            ->select(
                'users.id',
                DB::raw("TRIM(COALESCE(users.name,'') || ' ' || COALESCE(users.apellido_paterno,'')) as nombre"),
                'users.email',
                'users.puesto',
            )
            ->get();

        $equipos = DB::table('licencia_equipos')
            ->join('inventario_equipos', 'licencia_equipos.equipo_id', '=', 'inventario_equipos.id')
            ->where('licencia_equipos.licencia_id', $id)
            ->select(
                'inventario_equipos.id',
                'inventario_equipos.tipo',
                'inventario_equipos.cpu_serie',
                'inventario_equipos.cpu_marca',
                'inventario_equipos.cpu_modelo',
                'inventario_equipos.area',
            )
            ->get();

        $lic->titulares = $titulares;
        $lic->equipos   = $equipos;

        return response()->json($lic);
    }

    public function storeLicencia(Request $request)
    {
        $defaults = match ($request->input('tipo')) {
            'E3'             => ['max_usuarios' => 1, 'max_equipos' => 5],
            'E1'             => ['max_usuarios' => 1, 'max_equipos' => 0],
            'Exchange Plan 1'=> ['max_usuarios' => 1, 'max_equipos' => 0],
            default          => ['max_usuarios' => 1, 'max_equipos' => 0],
        };

        $data = $request->validate([
            'correo'        => 'required|email|unique:licencias,correo|max:150',
            'tipo'          => 'required|string|max:50',
            'max_usuarios'  => 'required|integer|min:1',
            'max_equipos'   => 'required|integer|min:0',
            'area'          => 'nullable|string',
            'estado'        => 'required|string|in:Activa,Inactiva,Suspendida',
            'caducidad'     => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        $id = DB::table('licencias')->insertGetId(array_merge($data, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));

        return response()->json(['ok' => true, 'id' => $id]);
    }

    public function updateLicencia(Request $request, $id)
    {
        abort_if(!DB::table('licencias')->where('id', $id)->exists(), 404);

        $data = $request->validate([
            'correo'        => "required|email|max:150|unique:licencias,correo,{$id}",
            'tipo'          => 'required|string|max:50',
            'max_usuarios'  => 'required|integer|min:1',
            'max_equipos'   => 'required|integer|min:0',
            'area'          => 'nullable|string',
            'estado'        => 'required|string|in:Activa,Inactiva,Suspendida',
            'caducidad'     => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        DB::table('licencias')->where('id', $id)->update(array_merge($data, ['updated_at' => now()]));

        return response()->json(['ok' => true]);
    }

    public function asignarLicenciaUsuario(Request $request, $id)
    {
        $lic = DB::table('licencias')->where('id', $id)->first();
        abort_if(!$lic, 404);

        $userId = $request->validate(['user_id' => 'required|exists:users,id'])['user_id'];

        $nActual = DB::table('licencia_users')->where('licencia_id', $id)->count();
        if ($nActual >= $lic->max_usuarios) {
            return response()->json(['error' => "Cupo de titulares alcanzado ({$lic->max_usuarios})."], 422);
        }

        DB::table('licencia_users')->updateOrInsert(
            ['licencia_id' => $id, 'user_id' => $userId],
            ['created_at'  => now()]
        );

        return response()->json(['ok' => true]);
    }

    public function desasignarLicenciaUsuario($id, $userId)
    {
        DB::table('licencia_users')->where('licencia_id', $id)->where('user_id', $userId)->delete();
        return response()->json(['ok' => true]);
    }

    public function asignarLicenciaEquipo(Request $request, $id)
    {
        $lic = DB::table('licencias')->where('id', $id)->first();
        abort_if(!$lic, 404);

        if ($lic->max_equipos <= 0) {
            return response()->json(['error' => 'Esta licencia no permite instalación en equipos (solo acceso web).'], 422);
        }

        $equipoId = $request->validate(['equipo_id' => 'required|exists:inventario_equipos,id'])['equipo_id'];

        $nActual = DB::table('licencia_equipos')->where('licencia_id', $id)->count();
        if ($nActual >= $lic->max_equipos) {
            return response()->json(['error' => "Cupo de equipos alcanzado ({$lic->max_equipos})."], 422);
        }

        DB::table('licencia_equipos')->updateOrInsert(
            ['licencia_id' => $id, 'equipo_id' => $equipoId],
            ['created_at'  => now()]
        );

        return response()->json(['ok' => true]);
    }

    public function desasignarLicenciaEquipo($id, $equipoId)
    {
        DB::table('licencia_equipos')->where('licencia_id', $id)->where('equipo_id', $equipoId)->delete();
        return response()->json(['ok' => true]);
    }
}
