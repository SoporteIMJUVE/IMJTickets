<?php

use Illuminate\Support\Facades\Route;
use Modules\Tickets\Http\Controllers\TicketsController;

// Formulario público — empleados sin login pueden abrir tickets
Route::get('/tickets/create',  [TicketsController::class, 'create'])->name('tickets.create');
Route::post('/tickets',        [TicketsController::class, 'store'])->name('tickets.store');

// Vista de gestión — solo personal autenticado
Route::middleware(['auth'])->prefix('tickets')->name('tickets.')->group(function () {
    Route::get('/', [TicketsController::class, 'index'])->name('index');
});
