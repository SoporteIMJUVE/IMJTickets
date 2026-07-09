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

    // JSON: equipos asignados a un empleado (para panel lateral)
    Route::get('/empleado/{id}/equipos', function ($id) {
        $empleado = \DB::table('empleados')->where('id_empleado', $id)->first();
        if (!$empleado) return response()->json([]);

        // ipv4 real: usa el campo populado; si está vacío busca en inventario_ips_completo vía serie
        $selectBase = [
            'inventario_equipos.id',
            'inventario_equipos.tipo',
            'inventario_equipos.num_inventario',
            'inventario_equipos.cpu_marca',
            'inventario_equipos.cpu_modelo',
            'inventario_equipos.cpu_serie',
            'inventario_equipos.mac',
            'inventario_equipos.area',
            'inventario_equipos.id_empleado',
            \DB::raw("COALESCE(
                NULLIF(TRIM(inventario_equipos.ipv4), ''),
                (SELECT ips.ip FROM inventario_ips_completo ips
                 WHERE LOWER(TRIM(ips.serie)) = LOWER(TRIM(inventario_equipos.cpu_serie))
                   AND ips.ip IS NOT NULL LIMIT 1)
            ) as ipv4"),
        ];

        // 1. Ligados por FK (vinculación formal)
        $porFk = \DB::table('inventario_equipos')
            ->where('inventario_equipos.id_empleado', $id)
            ->select($selectBase)
            ->orderBy('tipo')->orderBy('consecutivo')
            ->get()
            ->map(fn($e) => array_merge((array)$e, ['match' => 'fk']));

        // 2. Fallback: equipos sin FK cuyo nombre_usuario coincide con este empleado
        $nombre   = trim($empleado->nombre ?? '');
        $apellido = trim($empleado->apellido_paterno ?? '');
        $porNombre = collect();
        if ($nombre && $apellido) {
            $porNombre = \DB::table('inventario_equipos')
                ->whereNull('inventario_equipos.id_empleado')
                ->where('inventario_equipos.nombre_usuario', 'like', "%{$nombre}%")
                ->where('inventario_equipos.nombre_usuario', 'like', "%{$apellido}%")
                ->select($selectBase)
                ->orderBy('tipo')->orderBy('consecutivo')
                ->get()
                ->map(fn($e) => array_merge((array)$e, ['match' => 'nombre']));
        }

        return response()->json($porFk->concat($porNombre)->values());
    })->name('empleado.equipos');
});
