<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\IpAssigner;
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
            'equipos.*.tipo'           => 'required_with:equipos|string|in:Laptop,PC Avanzada,PC Especializada',
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
            'equipos.*.ipv4_actual'    => 'nullable|ip',
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
                'ipv4_actual'    => $equipo['ipv4_actual']    ?? null,
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
            'equipos.*.tipo'           => 'required_with:equipos|string|in:Laptop,PC Avanzada,PC Especializada',
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
            'equipos.*.ipv4_actual'    => 'nullable|ip',
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
                'ipv4_actual'    => $equipo['ipv4_actual']     ?? null,
                'ip_id'          => $ipId,
                'check_entrega'  => $equipo['check_entrega']   ?? null,
                'observaciones'  => $equipo['observaciones']   ?? null,
                'updated_at'     => now(),
            ];

            if (!empty($equipo['id'])) {
                // Equipo existente — solo actualizar si pertenece a este empleado
                DB::table('inventario_equipos')
                    ->where('id', $equipo['id'])
                    ->where('user_id', $id)
                    ->update($campos);
            } else {
                // Nuevo equipo agregado durante la edición
                DB::table('inventario_equipos')->insert(array_merge($campos, [
                    'user_id'           => $id,
                    'usuario_actual_id' => $id,
                    'created_at'        => now(),
                ]));
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
        // Baja lógica — no elimina el registro. Se fuerza una contraseña
        // aleatoria y se invalida el código de recuperación para que la
        // cuenta quede realmente inaccesible (ninguno de los dos mecanismos
        // de login — password o código — sigue funcionando).
        DB::table('users')->where('id', $id)->update([
            'activo'             => false,
            'fecha_baja'         => now(),
            'password'           => NewAccountProvisioner::tempPasswordHash(),
            'recovery_code_hash' => null,
            'updated_at'         => now(),
        ]);

        // Todos los equipos donde esta persona era responsable o usuario
        // actual regresan a Almacén: se desvincula, pero conservan su IP
        // asignada (mismo comportamiento que "Regresar a Almacén" en Kardex).
        DB::table('inventario_equipos')
            ->where('user_id', $id)
            ->orWhere('usuario_actual_id', $id)
            ->update([
                'estado'            => null,
                'user_id'           => null,
                'usuario_actual_id' => null,
                'updated_at'        => now(),
            ]);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('crm.index');
    }
}
