<?php
use Illuminate\Support\Facades\Route;
use Modules\Kardex\Http\Controllers\KardexController;

Route::middleware(['auth', 'admin'])->prefix('kardex')->name('kardex.')->group(function () {
    Route::get('/exportar/{tipo}', function (string $tipo, \Illuminate\Http\Request $request) {
        $exporter = match ($tipo) {
            'equipos'      => new \App\Exports\EquiposExporter(),
            'insumos'      => new \App\Exports\InsumosExporter(),
            'resguardos'   => new \App\Exports\ResguardosExporter(),
            'movimientos'  => new \App\Exports\MovimientosExporter(),
            default        => abort(404),
        };
        return $exporter->download($request->all());
    })->name('exportar');

    Route::get('/', [KardexController::class, 'index'])->name('index');

    // JSON: detalle de un equipo (para panel lateral)
    Route::get('/equipo/{id}', function ($id) {
        $eq = \DB::table('inventario_equipos')
            ->leftJoin('users as resp', 'inventario_equipos.user_id',          '=', 'resp.id')
            ->leftJoin('users as usu',  'inventario_equipos.usuario_actual_id', '=', 'usu.id')
            ->select(
                'inventario_equipos.*',
                \DB::raw("NULLIF(TRIM(COALESCE(resp.name,'') || ' ' || COALESCE(resp.apellido_paterno,'')), '') as empleado_nombre"),
                'resp.email as empleado_correo',
                \DB::raw("NULLIF(TRIM(COALESCE(usu.name,'') || ' ' || COALESCE(usu.apellido_paterno,'')), '')  as usuario_nombre"),
                'usu.email as usuario_correo',
                \DB::raw("COALESCE(
                    NULLIF(TRIM(inventario_equipos.ipv4), ''),
                    (SELECT ips.ip FROM inventario_ips_completo ips
                     WHERE LOWER(TRIM(ips.serie)) = LOWER(TRIM(inventario_equipos.cpu_serie))
                       AND ips.ip IS NOT NULL LIMIT 1)
                ) as ipv4_real"),
                \DB::raw("(SELECT ips.mac FROM inventario_ips_completo ips
                     WHERE LOWER(TRIM(ips.serie)) = LOWER(TRIM(inventario_equipos.cpu_serie))
                       AND ips.mac IS NOT NULL AND TRIM(ips.mac) NOT IN ('','/')
                     LIMIT 1) as mac_real"),
                \DB::raw("CASE WHEN EXISTS (
                    SELECT 1 FROM movimientos_equipos m
                    WHERE m.activo_id = inventario_equipos.id
                      AND m.tipo_activo = 'equipo'
                      AND m.tipo_evento = 'Entrada'
                ) THEN 0 ELSE 1 END as es_personal")
            )
            ->where('inventario_equipos.id', $id)
            ->first();
        abort_if(!$eq, 404);
        return response()->json($eq);
    })->name('equipo.detalle');

    // POST: cambia estado de un equipo
    Route::post('/equipo/{id}/estado',     [KardexController::class, 'cambiarEstadoEquipo'])->name('equipo.estado');

    // POST: cambia el usuario actual (quien usa el equipo físicamente)
    Route::post('/equipo/{id}/usuario', [KardexController::class, 'cambiarUsuarioEquipo'])->name('equipo.usuario');

    // POST: cambia estado de una impresora
    Route::post('/impresora/{id}/estado',  [KardexController::class, 'cambiarEstadoImpresora'])->name('impresora.estado');

    // GET: todos los movimientos (para tab Movimientos)
    Route::get('/movimientos/json', function () {
        $movs = \DB::table('movimientos_equipos')
            ->leftJoin('users as reg', 'movimientos_equipos.registrado_by', '=', 'reg.id')
            ->select(
                'movimientos_equipos.*',
                'reg.email as registrado_email',
            )
            ->orderByDesc('movimientos_equipos.created_at')
            ->get();

        // Enriquecer con datos del activo para la columna "Activo"
        return response()->json($movs->map(function ($m) {
            if ($m->tipo_activo === 'equipo') {
                $eq = \DB::table('inventario_equipos')->where('id', $m->activo_id)
                    ->select('cpu_serie', 'cpu_marca', 'cpu_modelo')->first();
                $m->activo_serie = $eq->cpu_serie ?? '—';
                $m->activo_desc  = trim(($eq->cpu_marca ?? '') . ' ' . ($eq->cpu_modelo ?? '')) ?: null;
            } else {
                $imp = \DB::table('impresoras')->where('id_impresora', $m->activo_id)
                    ->select('serie', 'marca', 'modelo')->first();
                $m->activo_serie = $imp->serie ?? '—';
                $m->activo_desc  = trim(($imp->marca ?? '') . ' ' . ($imp->modelo ?? '')) ?: null;
            }
            // Campo de búsqueda de texto plano para el filtro JS
            $m->texto_busqueda = strtolower(implode(' ', array_filter([
                $m->activo_serie, $m->activo_desc, $m->origen,
                $m->destino, $m->notas, $m->registrado_email,
            ])));
            return $m;
        }));
    })->name('movimientos.json');

    // GET: historial de movimientos (para panel lateral)
    Route::get('/equipo/{id}/historial', function ($id) {
        return response()->json(
            \DB::table('movimientos_equipos')
                ->leftJoin('users as reg', 'movimientos_equipos.registrado_by', '=', 'reg.id')
                ->where('tipo_activo', 'equipo')
                ->where('activo_id', $id)
                ->select(
                    'movimientos_equipos.*',
                    'reg.email as registrado_email',
                )
                ->orderByDesc('movimientos_equipos.created_at')
                ->get()
        );
    })->name('equipo.historial');

    Route::get('/impresora/{id}/historial', function ($id) {
        return response()->json(
            \DB::table('movimientos_equipos')
                ->leftJoin('users as reg', 'movimientos_equipos.registrado_by', '=', 'reg.id')
                ->where('tipo_activo', 'impresora')
                ->where('activo_id', $id)
                ->select(
                    'movimientos_equipos.*',
                    'reg.email as registrado_email',
                )
                ->orderByDesc('movimientos_equipos.created_at')
                ->get()
        );
    })->name('impresora.historial');

    // GET: descarga el PDF de resguardo (guardado en disco local)
    Route::get('/equipo/{id}/pdf', function ($id) {
        $eq = \DB::table('inventario_equipos')->where('id', $id)->first();
        abort_if(!$eq || !$eq->pdf_resguardo, 404);
        abort_if(!\Illuminate\Support\Facades\Storage::disk('local')->exists($eq->pdf_resguardo), 404);
        return \Illuminate\Support\Facades\Storage::disk('local')->download(
            $eq->pdf_resguardo,
            "resguardo-{$id}.pdf"
        );
    })->name('equipo.pdf');

    // GET: búsqueda de usuarios para el selector de responsable
    Route::get('/usuarios/buscar', function (\Illuminate\Http\Request $request) {
        $q = trim($request->query('q', ''));
        if (mb_strlen($q) < 2) return response()->json([]);

        return response()->json(
            \DB::table('users')
                ->leftJoin('departamentos', 'users.id_departamento', '=', 'departamentos.id_departamento')
                ->where('users.activo', 1)
                ->where(function ($query) use ($q) {
                    $query->where('users.name',             'like', "%{$q}%")
                          ->orWhere('users.apellido_paterno','like', "%{$q}%")
                          ->orWhere('users.email',           'like', "%{$q}%");
                })
                ->select(
                    'users.id as id_empleado',
                    'users.name as nombre',
                    'users.apellido_paterno',
                    'users.email as correo',
                    'users.puesto',
                    'departamentos.nombre as departamento'
                )
                ->orderBy('users.name')
                ->limit(20)
                ->get()
        );
    })->name('usuarios.buscar');

    Route::get('/resguardo/subir',      [KardexController::class, 'subirResguardo'])->name('resguardo.subir');
    Route::post('/resguardo/extraer',   [KardexController::class, 'extraerResguardo'])->name('resguardo.extraer');
    Route::get('/resguardo/preview',    [KardexController::class, 'mostrarPreview'])->name('resguardo.preview');
    Route::post('/resguardo/guardar',   [KardexController::class, 'guardarResguardo'])->name('resguardo.guardar');
    Route::get('/resguardo/ip',         [KardexController::class, 'sugerirIp'])->name('resguardo.ip');

    // JSON: busca un ticket por folio para vincular al resguardo.
    // Solo acepta tickets de tipo "Solicitud de Equipo".
    Route::get('/resguardo/ticket-buscar', function (\Illuminate\Http\Request $request) {
        $input = strtoupper(trim($request->input('folio', '')));
        if (!preg_match('/TK-(\d{4})-(\d+)/', $input, $m)) {
            return response()->json(['error' => 'Formato inválido. Ejemplo: TK-2026-0008'], 422);
        }
        $year = (int) $m[1];
        $id   = (int) $m[2];

        $t = \DB::table('tickets')
            ->where('id', $id)
            ->where('tipo', 'Solicitud de Equipo')
            ->first();

        if (!$t || \Carbon\Carbon::parse($t->created_at)->year !== $year) {
            return response()->json(['error' => 'No se encontró un ticket "Solicitud de Equipo" con ese folio.'], 404);
        }

        $folio  = '#TK-' . \Carbon\Carbon::parse($t->created_at)->format('Y') . '-' . str_pad($t->id, 4, '0', STR_PAD_LEFT);
        $estado = match ((int) $t->estado) { 0 => 'Cerrado', 1 => 'Abierto', 2 => 'Atendiendo', default => 'Desconocido' };

        return response()->json([
            'folio'        => $folio,
            'nombre'       => $t->nombre,
            'correo'       => $t->correo,
            'area'         => $t->area,
            'tipo'         => $t->tipo,
            'descripcion'  => $t->descripcion,
            'estado_label' => $estado,
            'hace'         => \Carbon\Carbon::parse($t->created_at)->diffForHumans(),
            'ip'           => $t->ip  ?? null,
            'mac'          => $t->mac ?? null,
        ]);
    })->name('resguardo.ticket-buscar');

    // JSON: verifica si una serie ya existe — permite que el formulario de preview
    // actualice el banner de operación cuando el admin corrige el número de serie.
    Route::get('/resguardo/verificar-serie', function (\Illuminate\Http\Request $request) {
        $serie = trim($request->query('serie', ''));
        if (strlen($serie) < 3) return response()->json(null);

        $eq = \DB::table('inventario_equipos as eq')
            ->leftJoin('users as resp', 'eq.user_id', '=', 'resp.id')
            ->where('eq.cpu_serie', $serie)
            ->select(
                'eq.id', 'eq.user_id', 'eq.tipo', 'eq.cpu_marca', 'eq.cpu_modelo', 'eq.area', 'eq.ipv4',
                \DB::raw("NULLIF(TRIM(COALESCE(resp.name,'') || ' ' || COALESCE(resp.apellido_paterno,'')), '') as responsable_nombre"),
                'resp.email as responsable_correo'
            )
            ->first();

        return response()->json($eq);
    })->name('resguardo.verificar-serie');
});
