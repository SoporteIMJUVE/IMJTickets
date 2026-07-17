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

    // JSON: IPs libres filtradas por area (para mini-modal de asignación en edición de equipo)
    Route::get('/ips-libres', function (\Illuminate\Http\Request $request) {
        if (!\Schema::hasTable('inventario_ips_completo')) return response()->json([]);
        $area = trim($request->query('area', ''));
        $query = \DB::table('inventario_ips_completo')->where('estatus', 'Libre');
        if ($area !== '') {
            $query->where(function ($q) use ($area) {
                $q->where('area_excel',           'like', "%{$area}%")
                  ->orWhere('departamento_pestana', 'like', "%{$area}%");
            });
        }
        return response()->json(
            $query->orderBy('ip')->limit(50)->pluck('ip')
        );
    })->name('ips.libres');

    // JSON: búsqueda de equipos/impresoras existentes (para evitar duplicados)
    Route::get('/equipos/buscar', function (\Illuminate\Http\Request $request) {
        $q = trim($request->query('q', ''));
        if (strlen($q) < 2) return response()->json([]);
        $like = "%{$q}%";

        $equipos = \DB::table('inventario_equipos as eq')
            ->leftJoin('users as u', 'eq.user_id', '=', 'u.id')
            ->where(function ($w) use ($like) {
                $w->where('eq.cpu_serie',    'like', $like)
                  ->orWhere('eq.nombre_equipo', 'like', $like)
                  ->orWhere('eq.cpu_marca',   'like', $like)
                  ->orWhere('eq.cpu_modelo',  'like', $like);
            })
            ->select(
                'eq.id', 'eq.tipo', 'eq.nombre_equipo',
                'eq.cpu_serie as serie', 'eq.cpu_marca as marca', 'eq.cpu_modelo as modelo',
                'eq.ipv4', 'eq.mac', 'eq.area', 'eq.extension', 'eq.observaciones',
                \DB::raw("CASE WHEN EXISTS (
                    SELECT 1 FROM movimientos_equipos m
                    WHERE m.activo_id = eq.id AND m.tipo_activo = 'equipo' AND m.tipo_evento = 'Entrada'
                ) THEN 0 ELSE 1 END as es_personal"),
                'eq.teclado_serie', 'eq.mouse_serie',
                'eq.monitor_marca', 'eq.monitor_modelo', 'eq.monitor_serie',
                'eq.nobreak_marca', 'eq.nobreak_modelo', 'eq.nobreak_serie',
                'eq.cargador_serie', 'eq.docking_marca', 'eq.docking_modelo', 'eq.docking_serie',
                'eq.candado', 'eq.check_entrega',
                \DB::raw("NULLIF(TRIM(COALESCE(u.name,'') || ' ' || COALESCE(u.apellido_paterno,'')), '') as responsable"),
                \DB::raw("'inventario_equipos' as tabla")
            )
            ->limit(8)->get();

        $impresoras = \DB::table('impresoras as imp')
            ->leftJoin('users as u', 'imp.user_id', '=', 'u.id')
            ->where(function ($w) use ($like) {
                $w->where('imp.serie', 'like', $like)
                  ->orWhere('imp.marca', 'like', $like)
                  ->orWhere('imp.modelo', 'like', $like);
            })
            ->select(
                'imp.id_impresora as id',
                \DB::raw("'Impresora' as tipo"),
                \DB::raw("NULL as nombre_equipo"),
                'imp.serie', 'imp.marca', 'imp.modelo',
                \DB::raw("NULL as ipv4"), \DB::raw("NULL as mac"),
                \DB::raw("NULL as area"), \DB::raw("NULL as extension"),
                \DB::raw("NULL as observaciones"),
                \DB::raw("NULLIF(TRIM(COALESCE(u.name,'') || ' ' || COALESCE(u.apellido_paterno,'')), '') as responsable"),
                \DB::raw("'impresoras' as tabla")
            )
            ->limit(4)->get();

        return response()->json($equipos->concat($impresoras)->values());
    })->name('equipos.buscar');

    // JSON: historial de movimientos Kardex del empleado (como origen o destino)
    Route::get('/empleado/{id}/movimientos', function ($id) {
        if (!\Schema::hasTable('movimientos_equipos')) return response()->json([]);

        return response()->json(
            \DB::table('movimientos_equipos')
                ->leftJoin('inventario_equipos', function ($join) {
                    $join->on('movimientos_equipos.activo_id', '=', 'inventario_equipos.id')
                         ->where('movimientos_equipos.tipo_activo', '=', 'equipo');
                })
                ->leftJoin('impresoras', function ($join) {
                    $join->on('movimientos_equipos.activo_id', '=', 'impresoras.id_impresora')
                         ->where('movimientos_equipos.tipo_activo', '=', 'impresora');
                })
                ->where(function ($q) use ($id) {
                    $q->where('movimientos_equipos.user_from_id', $id)
                      ->orWhere('movimientos_equipos.user_to_id', $id);
                })
                ->select(
                    'movimientos_equipos.tipo_evento',
                    'movimientos_equipos.tipo_activo',
                    'movimientos_equipos.origen',
                    'movimientos_equipos.destino',
                    'movimientos_equipos.estado_equipo',
                    'movimientos_equipos.notas',
                    'movimientos_equipos.created_at',
                    'inventario_equipos.tipo as equipo_tipo',
                    'inventario_equipos.cpu_marca',
                    'inventario_equipos.cpu_modelo',
                    'inventario_equipos.cpu_serie',
                    'inventario_equipos.nombre_equipo',
                    'impresoras.marca as imp_marca',
                    'impresoras.modelo as imp_modelo',
                    'impresoras.serie as imp_serie',
                )
                ->orderByDesc('movimientos_equipos.created_at')
                ->get()
        );
    })->name('empleado.movimientos');
});
