<?php
use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\CRMController;

Route::middleware(['auth'])->prefix('crm')->name('crm.')->group(function () {
    Route::post('/empleados',               [CRMController::class, 'store'])->name('empleados.store');
    Route::patch('/empleados/{id}',         [CRMController::class, 'update'])->name('empleados.update');
    Route::delete('/empleados/{id}',        [CRMController::class, 'destroy'])->name('empleados.destroy');
    Route::patch('/empleados/{id}/reactivar', [CRMController::class, 'reactivar'])->name('empleados.reactivar');

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

        // Patrón inter-módulo: DB::table('tickets') sin importar nada del módulo Tickets
        $ticketsPorCorreo = \Schema::hasTable('tickets')
            ? \DB::table('tickets')->get()
                ->groupBy('correo')
                ->map(fn($g) => $g->map(fn($t) => [
                    'id'          => $t->id,
                    'tipo'        => $t->tipo,
                    'area'        => $t->area,
                    'descripcion' => mb_substr($t->descripcion, 0, 120),
                    'estado'      => $t->estado,
                    'fecha'       => $t->created_at,
                ])->values()->all())
                ->all()
            : [];

        $departamentos = \DB::table('departamentos')->orderBy('nombre')->get();

        return view('crm::index', [
            'empleados'         => $empleados,
            'departamentos'     => $departamentos,
            'totalActivos'      => $empleados->where('activo', true)->count(),
            'totalDeptos'       => $departamentos->count(),
            'totalEquipos'      => \DB::table('inventario_equipos')->count(),
            'totalBajas'        => $empleados->where('activo', false)->count(),
            'ticketsPorCorreo'  => $ticketsPorCorreo,
        ]);
    })->name('index');
});
