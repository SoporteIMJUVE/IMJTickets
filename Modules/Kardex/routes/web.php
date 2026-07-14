<?php
use Illuminate\Support\Facades\Route;
use Modules\Kardex\Http\Controllers\KardexController;

Route::middleware(['auth', 'admin'])->prefix('kardex')->name('kardex.')->group(function () {
    Route::get('/exportar/{tipo}', function (string $tipo, \Illuminate\Http\Request $request) {
        $exporter = match ($tipo) {
            'equipos'    => new \App\Exports\EquiposExporter(),
            'insumos'    => new \App\Exports\InsumosExporter(),
            'resguardos' => new \App\Exports\ResguardosExporter(),
            default      => abort(404),
        };
        return $exporter->download($request->all());
    })->name('exportar');

    Route::get('/', [KardexController::class, 'index'])->name('index');

    // JSON: detalle de un equipo (para panel lateral)
    Route::get('/equipo/{id}', function ($id) {
        $eq = \DB::table('inventario_equipos')
            ->leftJoin('users', 'inventario_equipos.user_id', '=', 'users.id')
            ->select(
                'inventario_equipos.*',
                \DB::raw("NULLIF(TRIM(COALESCE(users.name,'') || ' ' || COALESCE(users.apellido_paterno,'')), '') as empleado_nombre"),
                'users.email as empleado_correo',
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
    Route::post('/equipo/{id}/estado',     [KardexController::class, 'cambiarEstadoEquipo'])->name('equipo.estado');

    // POST: cambia estado de una impresora
    Route::post('/impresora/{id}/estado',  [KardexController::class, 'cambiarEstadoImpresora'])->name('impresora.estado');

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
