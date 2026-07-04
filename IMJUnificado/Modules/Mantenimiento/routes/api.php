<?php

use Illuminate\Support\Facades\Route;
use Modules\Mantenimiento\Http\Controllers\MantenimientoController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('mantenimientos', MantenimientoController::class)->names('mantenimiento');
});
