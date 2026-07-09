<?php
use Illuminate\Support\Facades\Route;
use Modules\Kardex\Http\Controllers\KardexController;

Route::middleware(['auth'])->prefix('kardex')->name('kardex.')->group(function () {
    Route::get('/', function () {
        $equipos = \DB::table('inventario_equipos')
            ->leftJoin('empleados', 'inventario_equipos.id_empleado', '=', 'empleados.id_empleado')
            ->select(
                'inventario_equipos.*',
                \DB::raw("NULLIF(TRIM(COALESCE(empleados.nombre,'') || ' ' || COALESCE(empleados.apellido_paterno,'')), '') as empleado_nombre"),
                'empleados.correo as empleado_correo'
            )
            ->orderBy('inventario_equipos.tipo')
            ->orderBy('inventario_equipos.consecutivo')
            ->get();

        $insumos = \DB::table('insumos')->orderBy('nombre_insumo')->get();

        return view('kardex::index', [
            'equipos'       => $equipos,
            'insumos'       => $insumos,
            'totalEquipos'  => $equipos->count(),
            'enAlmacen'     => $equipos->filter(fn($e) => !$e->id_empleado && $e->estado !== 'mantenimiento' && $e->estado !== 'baja')->count(),
            'mantenimiento' => $equipos->where('estado', 'mantenimiento')->count(),
            'criticos'      => $insumos->filter(fn($i) => $i->stock_actual <= $i->stock_minimo)->count(),
            'totalInsumos'  => $insumos->sum('stock_actual'),
            'stockCritico'  => $insumos->filter(fn($i) => $i->stock_actual <= $i->stock_minimo)->count(),
        ]);
    })->name('index');

    // JSON: detalle de un equipo (para panel lateral)
    Route::get('/equipo/{id}', function ($id) {
        $eq = \DB::table('inventario_equipos')
            ->leftJoin('empleados', 'inventario_equipos.id_empleado', '=', 'empleados.id_empleado')
            ->select(
                'inventario_equipos.*',
                \DB::raw("NULLIF(TRIM(COALESCE(empleados.nombre,'') || ' ' || COALESCE(empleados.apellido_paterno,'')), '') as empleado_nombre"),
                'empleados.correo as empleado_correo',
                \DB::raw("COALESCE(
                    NULLIF(TRIM(inventario_equipos.ipv4), ''),
                    (SELECT ips.ip FROM inventario_ips_completo ips
                     WHERE LOWER(TRIM(ips.serie)) = LOWER(TRIM(inventario_equipos.cpu_serie))
                       AND ips.ip IS NOT NULL LIMIT 1)
                ) as ipv4_real"),
                \DB::raw("(SELECT ips.mac FROM inventario_ips_completo ips
                     WHERE LOWER(TRIM(ips.serie)) = LOWER(TRIM(inventario_equipos.cpu_serie))
                       AND ips.mac IS NOT NULL AND TRIM(ips.mac) NOT IN ('','/')
                     LIMIT 1) as mac_real")
            )
            ->where('inventario_equipos.id', $id)
            ->first();
        abort_if(!$eq, 404);
        return response()->json($eq);
    })->name('equipo.detalle');

    // POST: cambia estado de un equipo
    Route::post('/equipo/{id}/estado', function ($id, \Illuminate\Http\Request $request) {
        \DB::table('inventario_equipos')
            ->where('id', $id)
            ->update(['estado' => $request->estado ?: null, 'updated_at' => now()]);
        return response()->json(['ok' => true]);
    })->name('equipo.estado');

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

    Route::get('/resguardo/subir',      [KardexController::class, 'subirResguardo'])->name('resguardo.subir');
    Route::post('/resguardo/extraer',   [KardexController::class, 'extraerResguardo'])->name('resguardo.extraer');
    Route::get('/resguardo/preview',    [KardexController::class, 'mostrarPreview'])->name('resguardo.preview');
    Route::post('/resguardo/guardar',   [KardexController::class, 'guardarResguardo'])->name('resguardo.guardar');
    Route::get('/resguardo/ip',         [KardexController::class, 'sugerirIp'])->name('resguardo.ip');
});
