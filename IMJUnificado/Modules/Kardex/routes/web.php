<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('kardex')->name('kardex.')->group(function () {
    Route::get('/', function () {
        $equipos = \DB::table('inventario_equipos')
            ->leftJoin('empleados', 'inventario_equipos.id_empleado', '=', 'empleados.id_empleado')
            ->select('inventario_equipos.*',
                \DB::raw("(empleados.nombre || ' ' || COALESCE(empleados.apellido_paterno,'')) as empleado_nombre"))
            ->orderBy('inventario_equipos.tipo')
            ->orderBy('inventario_equipos.consecutivo')
            ->get();

        $insumos = \DB::table('insumos')->orderBy('nombre_insumo')->get();

        $resguardos = \DB::table('empleados')
            ->leftJoin('departamentos', 'empleados.id_departamento', '=', 'departamentos.id_departamento')
            ->join('inventario_equipos', 'empleados.id_empleado', '=', 'inventario_equipos.id_empleado')
            ->select(
                'empleados.*',
                'departamentos.nombre as departamento_nombre',
                \DB::raw('COUNT(inventario_equipos.id) as total_activos')
            )
            ->groupBy('empleados.id_empleado', 'departamentos.nombre')
            ->having('total_activos', '>', 0)
            ->orderBy('empleados.nombre')
            ->get();

        return view('kardex::index', [
            'equipos'       => $equipos,
            'insumos'       => $insumos,
            'resguardos'    => $resguardos,
            'totalEquipos'  => $equipos->count(),
            'enAlmacen'     => $equipos->whereNull('id_empleado')->count(),
            'mantenimiento' => 0,
            'criticos'      => $insumos->where('stock_actual', '<=', 2)->count(),
            'totalInsumos'  => $insumos->sum('stock_actual'),
            'stockCritico'  => $insumos->where('stock_actual', '<=', 2)->count(),
        ]);
    })->name('index');
});
