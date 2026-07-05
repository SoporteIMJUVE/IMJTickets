<?php

namespace Modules\CRM\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            'correo'            => 'nullable|email|max:120|unique:empleados,correo',
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

        $idEmpleado = DB::table('empleados')->insertGetId([
            'nombre'           => trim($validated['nombre']),
            'apellido_paterno' => trim($validated['apellido_paterno']),
            'apellido_materno' => trim($validated['apellido_materno'] ?? ''),
            'puesto'           => $validated['puesto'] ?? null,
            'correo'           => $validated['correo'] ?? null,
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
                'id_empleado'    => $idEmpleado,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
        }

        foreach ($validated['equipos'] ?? [] as $equipo) {
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
                'check_entrega'  => $equipo['check_entrega']  ?? null,
                'observaciones'  => $equipo['observaciones']  ?? null,
                'id_empleado'    => $idEmpleado,
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
            'correo'           => "nullable|email|max:120|unique:empleados,correo,{$id},id_empleado",
            'id_departamento'  => 'nullable|exists:departamentos,id_departamento',
        ], [
            'nombre.required'           => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'correo.unique'             => 'Este correo ya está registrado en otro usuario.',
            'correo.email'              => 'El formato del correo no es válido.',
        ]);

        DB::table('empleados')->where('id_empleado', $id)->update([
            'nombre'           => trim($validated['nombre']),
            'apellido_paterno' => trim($validated['apellido_paterno']),
            'apellido_materno' => trim($validated['apellido_materno'] ?? ''),
            'puesto'           => $validated['puesto'] ?? null,
            'correo'           => $validated['correo'] ?? null,
            'id_departamento'  => $validated['id_departamento'] ?? null,
            'updated_at'       => now(),
        ]);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return redirect()->route('crm.index');
    }

    public function reactivar($id)
    {
        DB::table('empleados')->where('id_empleado', $id)->update([
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
        // Baja lógica — no elimina el registro
        DB::table('empleados')->where('id_empleado', $id)->update([
            'activo'     => false,
            'fecha_baja' => now(),
            'updated_at' => now(),
        ]);

        if (request()->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('crm.index');
    }
}
