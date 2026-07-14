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

        // Resguardos: mismas filas de inventario_equipos, pero solo las que
        // realmente tienen el PDF de resguardo guardado — el punto en común
        // sigue siendo cpu_serie, no hace falta ningún join nuevo.
        $resguardos = $equipos->filter(fn ($e) => !empty($e->pdf_resguardo))->values();

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

        $equipo = DB::table('inventario_equipos')
            ->leftJoin('users', 'inventario_equipos.user_id', '=', 'users.id')
            ->select(
                'inventario_equipos.user_id',
                'inventario_equipos.estado as estado_actual',
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
                estado_equipo: 'Operativo',
            );

            return response()->json(['ok' => true]);
        }

        DB::table('inventario_equipos')->where('id', $id)->update([
            'estado'     => $estado,
            'updated_at' => now(),
        ]);

        $tipoEvento    = match($estado) {
            'mantenimiento' => 'Mantenimiento',
            'baja'          => 'Baja',
            default         => 'Almacén',
        };
        $estadoEquipo  = match($estado) {
            'mantenimiento' => 'En Reparación',
            'baja'          => 'Obsoleto',
            default         => 'Operativo',
        };

        KardexMovimiento::registrar(
            tipo_activo:   'equipo',
            activo_id:     (int) $id,
            tipo_evento:   $tipoEvento,
            origen:        $equipo->responsable_nombre ?? 'Sin responsable',
            user_from_id:  $equipo->user_id,
            estado_equipo: $estadoEquipo,
        );

        if (in_array($estado, ['mantenimiento', 'baja'], true)) {
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
                estado_equipo:'Operativo',
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
            'mantenimiento' => 'En Reparación',
            'baja'          => 'Obsoleto',
            default         => 'Operativo',
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
                ->where('users.activo', 1)
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

        return view('kardex::resguardo-preview', [
            'datos'      => session('resguardo.datos'),
            'textoRaw'   => session('resguardo.textoRaw'),
            'candidatos' => collect(session('resguardo.candidatos', []))->map(fn($e) => (object) $e),
            'directores' => $directores,
            'tmpPdf'     => session('resguardo.tmpPdf'),
            'esNativo'   => session('resguardo.esNativo', true),
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
            'nuevo_nombre'           => 'required_if:id_empleado,__nuevo__|string|max:80',
            'nuevo_apellido_paterno' => 'required_if:id_empleado,__nuevo__|string|max:80',
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

        // Verificar serie duplicada
        $existente = DB::table('inventario_equipos')
            ->where('cpu_serie', $request->cpu_serie)
            ->first();

        if ($existente) {
            return redirect()->route('kardex.resguardo.preview')->withInput()->withErrors([
                'cpu_serie' => "Ya existe un equipo con la serie {$request->cpu_serie} (ID: {$existente->id}).",
            ]);
        }

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

        // Mover PDF de tmp a almacenamiento permanente y registrar path
        $pdfPath = null;
        if ($request->tmp_pdf && Storage::disk('local')->exists($request->tmp_pdf)) {
            $pdfPath = "resguardos/{$idEquipo}.pdf";
            Storage::disk('local')->move($request->tmp_pdf, $pdfPath);
            DB::table('inventario_equipos')->where('id', $idEquipo)->update(['pdf_resguardo' => $pdfPath]);
        }

        // ── Ciclo de vida: ENTRADA → ASIGNACIÓN ─────────────────────────────
        // Todo equipo nuevo llega primero a Subdirección de Sistemas (desde el proveedor),
        // y de ahí se asigna al responsable. Ambos eventos siempre se registran.

        KardexMovimiento::registrar(
            tipo_activo:   'equipo',
            activo_id:     $idEquipo,
            tipo_evento:   'Entrada',
            origen:        'Proveedor',
            destino:       'Subdirección de Sistemas',
            estado_equipo: 'Nuevo',
        );

        $nombreEmpleado = DB::table('users')->where('id', $userId)
            ->selectRaw("NULLIF(TRIM(COALESCE(name,'') || ' ' || COALESCE(apellido_paterno,'')), '') as nombre")
            ->value('nombre') ?? 'Empleado';

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
            estado_equipo: 'Operativo',
            notas:         $notaAsignacion,
        );

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

        // Número de serie — busca patrones alfanuméricos de ≥5 chars cerca de "SERIE"
        $serie = $find('/NO[\.\s]*SERIE\s*[:\|]?\s*([A-Z0-9\-]{5,})/i');

        // Número de inventario
        $inventario = $find('/INVENTARIO\s*[:\|]?\s*(\d+)/i');

        // Marca y modelo — aparecen en la fila de la tabla de equipo
        $marca  = null;
        $modelo = null;
        if ($tipo) {
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
}
