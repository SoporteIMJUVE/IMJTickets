<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('crm')->name('crm.')->group(function () {
    Route::get('/', function () {
        $empleados = \DB::table('empleados')
            ->leftJoin('departamentos', 'empleados.id_departamento', '=', 'departamentos.id_departamento')
            ->leftJoin('telefonos', 'empleados.id_empleado', '=', 'telefonos.id_empleado')
            ->leftJoin('inventario_equipos', 'empleados.id_empleado', '=', 'inventario_equipos.id_empleado')
            ->select(
                'empleados.*',
                'departamentos.nombre as departamento_nombre',
                'telefonos.extension',
                \DB::raw('COUNT(inventario_equipos.id) as total_equipos')
            )
            ->groupBy('empleados.id_empleado', 'departamentos.nombre', 'telefonos.extension')
            ->orderBy('empleados.nombre')
            ->get();

        return view('crm::index', [
            'empleados'       => $empleados,
            'totalActivos'    => $empleados->where('activo', true)->count(),
            'totalDeptos'     => \DB::table('departamentos')->count(),
            'totalEquipos'    => \DB::table('inventario_equipos')->count(),
            'totalBajas'      => $empleados->where('activo', false)->count(),
        ]);
    })->name('index');
});
