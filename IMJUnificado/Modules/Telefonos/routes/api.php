<?php

use Illuminate\Support\Facades\Route;
use Modules\Telefonos\Http\Controllers\TelefonosController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('telefonos', TelefonosController::class)->names('telefonos');
});
