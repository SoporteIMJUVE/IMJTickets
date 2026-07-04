<?php

use Illuminate\Support\Facades\Route;
use Modules\Kardex\Http\Controllers\KardexController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('kardexes', KardexController::class)->names('kardex');
});
