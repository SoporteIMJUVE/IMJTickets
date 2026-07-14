<?php
use Illuminate\Support\Facades\Route;
use Modules\CRM\Http\Controllers\CRMController;

Route::middleware(['auth', 'admin'])->prefix('crm')->name('crm.')->group(function () {
    Route::post('/empleados',               [CRMController::class, 'store'])->name('empleados.store');
    Route::patch('/empleados/{id}',         [CRMController::class, 'update'])->name('empleados.update');
    Route::delete('/empleados/{id}',        [CRMController::class, 'destroy'])->name('empleados.destroy');
    Route::patch('/empleados/{id}/reactivar', [CRMController::class, 'reactivar'])->name('empleados.reactivar');

    Route::get('/exportar', function (\Illuminate\Http\Request $request) {
        return (new \App\Exports\EmpleadosExporter())->download($request->all());
    })->name('exportar');

    Route::get('/', function () {
        $empleados = \DB::table('users')
            ->leftJoin('departamentos', 'users.id_departamento', '=', 'departamentos.id_departamento')
            ->leftJoin('inventario_equipos', 'users.id', '=', 'inventario_equipos.user_id')
            ->select(
                'users.id as id_empleado',
                'users.name as nombre',
                'users.apellido_paterno',
                'users.apellido_materno',
                'users.puesto',
                'users.email as correo',
                'users.id_departamento',
                'users.activo',
                'users.fecha_alta',
                'users.fecha_baja',
                'departamentos.nombre as departamento_nombre',
                \DB::raw("(SELECT ie.extension FROM inventario_equipos ie WHERE ie.tipo = 'Telefono' AND ie.user_id = users.id LIMIT 1) as extension"),
                \DB::raw('COUNT(inventario_equipos.id) as total_equipos')
            )
            ->where('users.role', 'user')
            ->groupBy('users.id', 'departamentos.nombre')
            ->orderBy('users.name')
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
        $empleado = \DB::table('users')->where('id', $id)->first();
        if (!$empleado) return response()->json([]);
        $empleado->nombre = $empleado->name;

        // ipv4 real: usa el campo populado; si está vacío busca en inventario_ips_completo vía serie
        $selectBase = [
            'inventario_equipos.id',
            'inventario_equipos.tipo',
            'inventario_equipos.num_inventario',
            'inventario_equipos.nombre_equipo',
            'inventario_equipos.cpu_marca',
            'inventario_equipos.cpu_modelo',
            'inventario_equipos.cpu_serie',
            'inventario_equipos.teclado_serie',
            'inventario_equipos.mouse_serie',
            'inventario_equipos.monitor_marca',
            'inventario_equipos.monitor_modelo',
            'inventario_equipos.monitor_serie',
            'inventario_equipos.nobreak_marca',
            'inventario_equipos.nobreak_modelo',
            'inventario_equipos.nobreak_serie',
            'inventario_equipos.cargador_serie',
            'inventario_equipos.docking_marca',
            'inventario_equipos.docking_modelo',
            'inventario_equipos.docking_serie',
            'inventario_equipos.candado',
            'inventario_equipos.mac',
            'inventario_equipos.check_entrega',
            'inventario_equipos.observaciones',
            'inventario_equipos.area',
            'inventario_equipos.user_id as id_empleado',
            \DB::raw("COALESCE(
                NULLIF(TRIM(inventario_equipos.ipv4), ''),
                (SELECT ips.ip FROM inventario_ips_completo ips
                 WHERE LOWER(TRIM(ips.serie)) = LOWER(TRIM(inventario_equipos.cpu_serie))
                   AND ips.ip IS NOT NULL LIMIT 1)
            ) as ipv4"),
        ];

        // 1. Ligados por FK (vinculación formal)
        $porFk = \DB::table('inventario_equipos')
            ->where('inventario_equipos.user_id', $id)
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
                ->whereNull('inventario_equipos.user_id')
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
