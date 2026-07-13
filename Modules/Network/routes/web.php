<?php
use Illuminate\Support\Facades\Route;
use Modules\Network\Http\Controllers\NetworkController;

Route::middleware(['auth', 'admin'])->prefix('network')->name('network.')->group(function () {
    Route::get('/exportar', function (\Illuminate\Http\Request $request) {
        return (new \App\Exports\IpsExporter())->download($request->all());
    })->name('exportar');

    Route::get('/', [NetworkController::class, 'index'])->name('index');

    Route::get('/ip/{id}/detalle', [NetworkController::class, 'detalle'])->name('ip.detalle');
    Route::patch('/ip/{id}/configuracion', [NetworkController::class, 'guardarConfiguracion'])->name('ip.configuracion');
    Route::post('/ip/{id}/liberar-estado', [NetworkController::class, 'liberarPorEstado'])->name('ip.liberar-estado');
    Route::post('/ip/{id}/liberar-switch', [NetworkController::class, 'liberarPorSwitch'])->name('ip.liberar-switch');
});
