<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\IpAssigner;
use App\Support\KardexMovimiento;
use App\Support\NewAccountProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CRMController extends Controller
{
    public function index()
    {
        return view('crm::index');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre'            => 'required|string|max:80',
            'apellido_paterno'  => 'required|string|max:80',
            'apellido_materno'  => 'nullable|string|max:80',
            'puesto'            => 'nullable|string|max:120',
            'correo'            => 'nullable|email|max:120|unique:users,email',
            'id_departamento'   => 'nullable|exists:departamentos,id_departamento',
            // Teléfono (opcional)
            'tel_numero'        => 'nullable|string|max:30',
            'tel_extension'     => 'nullable|integer',
            // Equipos (array, opcional)
            'equipos'           => 'nullable|array|max:10',
            'equipos.*.tipo'           => 'required_with:equipos|string|in:Laptop,PC Avanzada,PC Especializada,Telefono',
            'equipos.*.tabla'          => 'nullable|string|in:inventario_equipos,impresoras',
            'equipos.*.nombre_equipo'  => 'nullable|string|max:60',
            'equipos.*.cpu_marca'      => 'nullable|string|max:60',
            'equipos.*.cpu_modelo'     => 'nullable|string|max:100',
            'equipos.*.cpu_serie'      => 'nullable|string|max:100',
            'equipos.*.teclado_serie'  => 'nullable|string|max:100',
            'equipos.*.mouse_serie'    => 'nullable|string|max:100',
            'equipos.*.monitor_marca'  => 'nullable|string|max:60',
            'equipos.*.monitor_modelo' => 'nullable|string|max:100',
            'equipos.*.monitor_serie'  => 'nullable|string|max:100',
            'equipos.*.nobreak_marca'  => 'nullable|string|max:60',
            'equipos.*.nobreak_modelo' => 'nullable|string|max:100',
            'equipos.*.nobreak_serie'  => 'nullable|string|max:100',
            'equipos.*.cargador_serie' => 'nullable|string|max:100',
            'equipos.*.docking_marca'  => 'nullable|string|max:60',
            'equipos.*.docking_modelo' => 'nullable|string|max:100',
            'equipos.*.docking_serie'  => 'nullable|string|max:100',
            'equipos.*.candado'        => 'nullable|string|max:50',
            'equipos.*.mac'            => 'nullable|string|max:30',
            'equipos.*.ipv4'           => 'nullable|ip',
            'equipos.*.extension'      => 'nullable|integer',
            'equipos.*.check_entrega'  => 'nullable|string|max:50',
            'equipos.*.observaciones'  => 'nullable|string',
        ], [
            'nombre.required'           => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'correo.unique'             => 'Este correo ya está registrado.',
            'correo.email'              => 'El formato del correo no es válido.',
            'equipos.*.tipo.required_with' => 'Selecciona el tipo de equipo.',
            'equipos.*.ipv4.ip'            => 'El formato de la IPv4 no es válido.',
        ]);

        // Resolver las IPs de los equipos contra el registro maestro ANTES de
        // crear nada, para no dejar altas a medias si alguna IP ya está tomada.
        $ipsPorEquipo = [];
        foreach ($validated['equipos'] ?? [] as $i => $equipo) {
            if (empty($equipo['ipv4'])) continue;
            $ipId = IpAssigner::resolveId($equipo['ipv4']);
            if (IpAssigner::assignedElsewhere($ipId, 'inventario_equipos')) {
                return back()->withInput()->withErrors([
                    "equipos.{$i}.ipv4" => "La IP {$equipo['ipv4']} ya está asignada a otro dispositivo.",
                ]);
            }
            $ipsPorEquipo[$i] = $ipId;
        }

        // Sin correo -> cuenta con email/placeholder temporal (mismo mecanismo que la
        // migración masiva empleados->users; el acceso real llega con el paso 2).
        $correo = $validated['correo'] ?? null;
        if (!$correo) {
            $correo = NewAccountProvisioner::placeholderEmail(Str::random(8));
        }

        $idEmpleado = DB::table('users')->insertGetId([
            'name'             => trim($validated['nombre']),
            'apellido_paterno' => trim($validated['apellido_paterno']),
            'apellido_materno' => trim($validated['apellido_materno'] ?? ''),
            'email'            => $correo,
            'password'         => NewAccountProvisioner::tempPasswordHash(),
            'role'             => 'user',
            'puesto'           => $validated['puesto'] ?? null,
            'id_departamento'  => $validated['id_departamento'] ?? null,
            'activo'           => true,
            'fecha_alta'       => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        if (!empty($validated['tel_numero']) || !empty($validated['tel_extension'])) {
            DB::table('telefonos')->insert([
                'numero_general' => $validated['tel_numero'] ?? null,
                'extension'      => $validated['tel_extension'] ?? null,
                'user_id'        => $idEmpleado,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        foreach ($validated['equipos'] ?? [] as $i => $equipo) {
            if (empty($equipo['tipo'])) continue;
            DB::table('inventario_equipos')->insert([
                'tipo'           => $equipo['tipo'],
                'nombre_equipo'  => $equipo['nombre_equipo']  ?? null,
                'cpu_marca'      => $equipo['cpu_marca']      ?? null,
                'cpu_modelo'     => $equipo['cpu_modelo']     ?? null,
                'cpu_serie'      => $equipo['cpu_serie']      ?? null,
                'teclado_serie'  => $equipo['teclado_serie']  ?? null,
                'mouse_serie'    => $equipo['mouse_serie']    ?? null,
                'monitor_marca'  => $equipo['monitor_marca']  ?? null,
                'monitor_modelo' => $equipo['monitor_modelo'] ?? null,
                'monitor_serie'  => $equipo['monitor_serie']  ?? null,
                'nobreak_marca'  => $equipo['nobreak_marca']  ?? null,
                'nobreak_modelo' => $equipo['nobreak_modelo'] ?? null,
                'nobreak_serie'  => $equipo['nobreak_serie']  ?? null,
                'cargador_serie' => $equipo['cargador_serie'] ?? null,
                'docking_marca'  => $equipo['docking_marca']  ?? null,
                'docking_modelo' => $equipo['docking_modelo'] ?? null,
                'docking_serie'  => $equipo['docking_serie']  ?? null,
                'candado'        => $equipo['candado']        ?? null,
                'mac'            => $equipo['mac']            ?? null,
                'ipv4'           => $equipo['ipv4']           ?? null,
                'extension'      => $equipo['extension']      ?? null,
                'ip_id'          => $ipsPorEquipo[$i] ?? null,
                'check_entrega'  => $equipo['check_entrega']  ?? null,
                'observaciones'  => $equipo['observaciones']  ?? null,
                'user_id'           => $idEmpleado,
                'usuario_actual_id' => $idEmpleado,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'id' => $idEmpleado]);
        }

        return redirect()->route('crm.index')->with('success', "Usuario {$validated['nombre']} {$validated['apellido_paterno']} dado de alta.");
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'nombre'           => 'required|string|max:80',
            'apellido_paterno' => 'required|string|max:80',
            'apellido_materno' => 'nullable|string|max:80',
            'puesto'           => 'nullable|string|max:120',
            'correo'           => "nullable|email|max:120|unique:users,email,{$id},id",
            'id_departamento'  => 'nullable|exists:departamentos,id_departamento',
            'equipos'                  => 'nullable|array|max:20',
            'equipos.*.id'             => 'nullable|integer',
            'equipos.*.tipo'           => 'required_with:equipos|string|in:Laptop,PC Avanzada,PC Especializada,Telefono,Impresora',
            'equipos.*.tabla'          => 'nullable|string|in:inventario_equipos,impresoras',
            'equipos.*.nombre_equipo'  => 'nullable|string|max:60',
            'equipos.*.cpu_marca'      => 'nullable|string|max:60',
            'equipos.*.cpu_modelo'     => 'nullable|string|max:100',
            'equipos.*.cpu_serie'      => 'nullable|string|max:100',
            'equipos.*.teclado_serie'  => 'nullable|string|max:100',
            'equipos.*.mouse_serie'    => 'nullable|string|max:100',
            'equipos.*.monitor_marca'  => 'nullable|string|max:60',
            'equipos.*.monitor_modelo' => 'nullable|string|max:100',
            'equipos.*.monitor_serie'  => 'nullable|string|max:100',
            'equipos.*.nobreak_marca'  => 'nullable|string|max:60',
            'equipos.*.nobreak_modelo' => 'nullable|string|max:100',
            'equipos.*.nobreak_serie'  => 'nullable|string|max:100',
            'equipos.*.cargador_serie' => 'nullable|string|max:100',
            'equipos.*.docking_marca'  => 'nullable|string|max:60',
            'equipos.*.docking_modelo' => 'nullable|string|max:100',
            'equipos.*.docking_serie'  => 'nullable|string|max:100',
            'equipos.*.candado'        => 'nullable|string|max:50',
            'equipos.*.mac'            => 'nullable|string|max:30',
            'equipos.*.ipv4'           => 'nullable|ip',
            'equipos.*.extension'      => 'nullable|integer',
            'equipos.*.check_entrega'  => 'nullable|string|max:50',
            'equipos.*.observaciones'  => 'nullable|string',
        ], [
            'nombre.required'           => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'correo.unique'             => 'Este correo ya está registrado en otro usuario.',
            'correo.email'              => 'El formato del correo no es válido.',
        ]);

        $datosUsuario = [
            'name'             => trim($validated['nombre']),
            'apellido_paterno' => trim($validated['apellido_paterno']),
            'apellido_materno' => trim($validated['apellido_materno'] ?? ''),
            'puesto'           => $validated['puesto'] ?? null,
            'id_departamento'  => $validated['id_departamento'] ?? null,
            'updated_at'       => now(),
        ];

        // 'email' es NOT NULL en users — solo se toca si el operador capturó un
        // correo real; dejarlo en blanco no borra el correo/placeholder actual.
        if (!empty($validated['correo'])) {
            $datosUsuario['email'] = $validated['correo'];
        }

        DB::table('users')->where('id', $id)->update($datosUsuario);

        foreach ($validated['equipos'] ?? [] as $equipo) {
            if (empty($equipo['tipo'])) continue;

            // Impresora seleccionada desde búsqueda: solo actualizar user_id
            if (($equipo['tabla'] ?? null) === 'impresoras' && !empty($equipo['id'])) {
                DB::table('impresoras')
                    ->where('id_impresora', $equipo['id'])
                    ->update(['user_id' => $id, 'updated_at' => now()]);
                continue;
            }

            // Leer estado actual del equipo (sin restricción de user_id para permitir switch)
            $actual      = null;
            $ipAnterior  = null;
            $serieEquipo = null;
            $userAnterior = null;
            if (!empty($equipo['id'])) {
                $actual       = DB::table('inventario_equipos')
                    ->where('id', $equipo['id'])
                    ->select('ipv4', 'cpu_serie', 'user_id')
                    ->first();
                $ipAnterior   = $actual->ipv4     ?? null;
                $serieEquipo  = $actual->cpu_serie ?? null;
                $userAnterior = $actual->user_id   ?? null;

                // Sin evento 'Entrada' en Kardex → equipo personal → no se puede reasignar
                $tieneResguardo = \Schema::hasTable('movimientos_equipos') && DB::table('movimientos_equipos')
                    ->where('activo_id', $equipo['id'])
                    ->where('tipo_activo', 'equipo')
                    ->where('tipo_evento', 'Entrada')
                    ->exists();

                if (!$tieneResguardo && $userAnterior && (int) $userAnterior !== (int) $id) {
                    return back()->withInput()->withErrors([
                        'equipos' => 'El equipo ' . ($actual->cpu_serie ?? "#{$equipo['id']}") . ' es propiedad personal del empleado actual y no puede reasignarse.',
                    ]);
                }
            }

            $ipId = null;
            if (!empty($equipo['ipv4'])) {
                $ipId = IpAssigner::resolveId($equipo['ipv4']);
                if (IpAssigner::assignedElsewhere($ipId, 'inventario_equipos', $equipo['id'] ?? null)) {
                    return back()->withInput()->withErrors([
                        'equipos' => "La IP {$equipo['ipv4']} ya está asignada a otro dispositivo.",
                    ]);
                }
            }

            $campos = [
                'tipo'           => $equipo['tipo'],
                'nombre_equipo'  => $equipo['nombre_equipo']  ?? null,
                'cpu_marca'      => $equipo['cpu_marca']       ?? null,
                'cpu_modelo'     => $equipo['cpu_modelo']      ?? null,
                'cpu_serie'      => $equipo['cpu_serie']       ?? null,
                'teclado_serie'  => $equipo['teclado_serie']   ?? null,
                'mouse_serie'    => $equipo['mouse_serie']     ?? null,
                'monitor_marca'  => $equipo['monitor_marca']   ?? null,
                'monitor_modelo' => $equipo['monitor_modelo']  ?? null,
                'monitor_serie'  => $equipo['monitor_serie']   ?? null,
                'nobreak_marca'  => $equipo['nobreak_marca']   ?? null,
                'nobreak_modelo' => $equipo['nobreak_modelo']  ?? null,
                'nobreak_serie'  => $equipo['nobreak_serie']   ?? null,
                'cargador_serie' => $equipo['cargador_serie']  ?? null,
                'docking_marca'  => $equipo['docking_marca']   ?? null,
                'docking_modelo' => $equipo['docking_modelo']  ?? null,
                'docking_serie'  => $equipo['docking_serie']   ?? null,
                'candado'        => $equipo['candado']         ?? null,
                'mac'            => $equipo['mac']             ?? null,
                'ipv4'           => $equipo['ipv4']            ?? null,
                'extension'      => $equipo['extension']       ?? null,
                'ip_id'          => $ipId,
                'check_entrega'  => $equipo['check_entrega']   ?? null,
                'observaciones'  => $equipo['observaciones']   ?? null,
                'user_id'        => $id,
                'updated_at'     => now(),
            ];

            if (!empty($equipo['id'])) {
                DB::table('inventario_equipos')->where('id', $equipo['id'])->update($campos);

                $hayKardex = \Schema::hasTable('movimientos_equipos');

                // Reasignación: el equipo pasó de manos
                if ($hayKardex && $userAnterior && (int) $userAnterior !== (int) $id) {
                    $nombreAnterior = DB::table('users')->where('id', $userAnterior)->value('name') ?? '—';
                    $nombreNuevo    = DB::table('users')->where('id', $id)->value('name') ?? '—';
                    KardexMovimiento::registrar(
                        tipo_activo:   'equipo',
                        activo_id:     (int) $equipo['id'],
                        tipo_evento:   'Reasignación',
                        origen:        $nombreAnterior,
                        destino:       $nombreNuevo,
                        user_from_id:  (int) $userAnterior,
                        user_to_id:    (int) $id,
                        estado_equipo: 'Asignado',
                        notas:         'Cambio de responsable desde panel CRM.',
                    );
                }

                // Evento de IP si cambió
                $nuevaIp = $equipo['ipv4'] ?? null;
                if ($hayKardex && $nuevaIp && $nuevaIp !== $ipAnterior) {
                    KardexMovimiento::registrar(
                        tipo_activo:   'equipo',
                        activo_id:     (int) $equipo['id'],
                        tipo_evento:   $ipAnterior ? 'Cambio IP' : 'Asignación IP',
                        origen:        $ipAnterior ? ($serieEquipo ?? '—') : 'Sin equipo',
                        destino:       $serieEquipo ?? '—',
                        estado_equipo: 'Ocupada',
                        notas:         $ipAnterior
                            ? "IP: {$ipAnterior} → {$nuevaIp}"
                            : "IP: {$nuevaIp}",
                    );
                }
            } else {
                // Equipo nuevo registrado desde CRM (sin resguardo → sin evento Entrada)
                $nuevoId = DB::table('inventario_equipos')->insertGetId(array_merge($campos, [
                    'usuario_actual_id' => $id,
                    'created_at'        => now(),
                ]));

                // Registrar evento de IP si se capturó una
                if (!empty($equipo['ipv4']) && \Schema::hasTable('movimientos_equipos')) {
                    KardexMovimiento::registrar(
                        tipo_activo:   'equipo',
                        activo_id:     $nuevoId,
                        tipo_evento:   'Asignación IP',
                        origen:        'Sin equipo',
                        destino:       $equipo['cpu_serie'] ?? '—',
                        estado_equipo: 'Ocupada',
                        notas:         "IP: {$equipo['ipv4']}",
                    );
                }
            }
        }

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return redirect()->route('crm.index');
    }

    public function reactivar($id)
    {
        DB::table('users')->where('id', $id)->update([
            'activo'     => true,
            'fecha_baja' => null,
            'updated_at' => now(),
        ]);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return redirect()->route('crm.index');
    }

    public function destroy($id)
    {
        $empleado = DB::table('users')
            ->leftJoin('departamentos', 'users.id_departamento', '=', 'departamentos.id_departamento')
            ->select('users.*', 'departamentos.nombre as departamento_nombre')
            ->where('users.id', $id)
            ->first();

        abort_if(!$empleado, 404);

        $nombreEmpleado = trim(implode(' ', array_filter([
            $empleado->name, $empleado->apellido_paterno,
        ])));
        $areaEmpleado = $empleado->departamento_nombre ?? 'Sin área';

        $admin       = DB::table('users')->where('role', 'admin')->select('id', 'name', 'email')->first();
        $adminEmail  = $admin?->email ?? 'sistemas@imjuventud.gob.mx';
        $adminNombre = $admin?->name ?? 'Subdirección de Sistemas';

        // ── 1. Baja lógica del usuario ───────────────────────────────────
        DB::table('users')->where('id', $id)->update([
            'activo'             => false,
            'fecha_baja'         => now(),
            'password'           => NewAccountProvisioner::tempPasswordHash(),
            'recovery_code_hash' => null,
            'updated_at'         => now(),
        ]);

        // ── 2. Clasificar equipos del empleado ───────────────────────────
        $equipos = DB::table('inventario_equipos')
            ->where(function ($q) use ($id) {
                $q->where('user_id', $id)->orWhere('usuario_actual_id', $id);
            })
            ->select('id', 'cpu_serie', 'cpu_marca', 'cpu_modelo', 'tipo', 'area', 'ipv4', 'ip_id')
            ->get();

        if ($equipos->isNotEmpty()) {
            $idsInstitucionales = DB::table('movimientos_equipos')
                ->where('tipo_activo', 'equipo')
                ->where('tipo_evento', 'Entrada')
                ->whereIn('activo_id', $equipos->pluck('id'))
                ->pluck('activo_id')
                ->flip();

            foreach ($equipos as $eq) {
                if ($idsInstitucionales->has($eq->id)) {
                    // Institucional: Almacén, conservar IP, evento Kardex + ticket
                    DB::table('inventario_equipos')->where('id', $eq->id)->update([
                        'estado'            => null,
                        'user_id'           => null,
                        'usuario_actual_id' => null,
                        'nombre_usuario'    => null,
                        'updated_at'        => now(),
                    ]);
                    KardexMovimiento::registrar(
                        tipo_activo:   'equipo',
                        activo_id:     (int) $eq->id,
                        tipo_evento:   'Almacén',
                        origen:        $nombreEmpleado,
                        destino:       'Almacén',
                        user_from_id:  (int) $id,
                        estado_equipo: 'Almacén',
                        notas:         "Desvinculado por baja del empleado — ref. usuario #{$id}",
                    );
                    // Ticket automático para que el admin reasigne el resguardo
                    $desc = trim("{$eq->tipo} {$eq->cpu_marca} {$eq->cpu_modelo}");
                    DB::table('tickets')->insert([
                        'nombre'      => $adminNombre,
                        'correo'      => $adminEmail,
                        'area'        => $areaEmpleado,
                        'tipo'        => 'Solicitud de Equipo',
                        'descripcion' => "El empleado {$nombreEmpleado} fue dado de baja (ref. usuario #{$id}). "
                                       . "El equipo {$desc} (No. serie: {$eq->cpu_serie}) quedó en Almacén. "
                                       . "Se requiere asignar nuevo responsable de resguardo.",
                        'estado'      => 1,
                        'ip'          => request()->ip() ?? '127.0.0.1',
                        'created_at'  => now(),
                        'updated_at'  => now(),
                    ]);
                } else {
                    // Personal: baja + liberar IP
                    DB::table('inventario_equipos')->where('id', $eq->id)->update([
                        'estado'            => 'baja',
                        'user_id'           => null,
                        'usuario_actual_id' => null,
                        'nombre_usuario'    => null,
                        'updated_at'        => now(),
                    ]);
                    KardexMovimiento::registrar(
                        tipo_activo:   'equipo',
                        activo_id:     (int) $eq->id,
                        tipo_evento:   'Baja',
                        origen:        $areaEmpleado,
                        destino:       'Proveedor',
                        user_from_id:  (int) $id,
                        estado_equipo: 'Baja',
                        notas:         "Equipo personal dado de baja por baja del empleado — ref. usuario #{$id}",
                    );
                    if ($eq->ipv4) {
                        KardexMovimiento::registrar(
                            tipo_activo:   'equipo',
                            activo_id:     (int) $eq->id,
                            tipo_evento:   'Liberación IP',
                            origen:        $eq->cpu_serie ?? '—',
                            destino:       'Sin equipo',
                            estado_equipo: 'Libre',
                            notas:         "IP: {$eq->ipv4}",
                        );
                        IpAssigner::liberarEquipo((int) $eq->id);
                    }
                }
            }
        }

        // ── 3. Impresoras — desvinculan (bug #2) ────────────────────────
        DB::table('impresoras')
            ->where('user_id', $id)
            ->update(['user_id' => null, 'updated_at' => now()]);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('crm.index');
    }
}
