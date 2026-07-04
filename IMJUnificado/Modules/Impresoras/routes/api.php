<?php

use Illuminate\Support\Facades\Route;
use Modules\Impresoras\Http\Controllers\ImpresorasController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('impresoras', ImpresorasController::class)->names('impresoras');
});
