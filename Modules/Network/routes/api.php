<?php

use Illuminate\Support\Facades\Route;
use Modules\Network\Http\Controllers\NetworkController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('networks', NetworkController::class)->names('network');
});
