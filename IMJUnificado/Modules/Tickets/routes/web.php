<?php

use Illuminate\Support\Facades\Route;
use Modules\Tickets\Http\Controllers\TicketsController;

// Formulario público — empleados sin login pueden abrir tickets
Route::get('/tickets/create',  [TicketsController::class, 'create'])->name('tickets.create');
Route::post('/tickets',        [TicketsController::class, 'store'])->name('tickets.store');

// Vista de gestión + API interna — solo personal autenticado
Route::middleware(['auth'])->prefix('tickets')->name('tickets.')->group(function () {
    Route::get('/',                        [TicketsController::class, 'index'])->name('index');
    Route::patch('/{id}/estado',           [TicketsController::class, 'cambiarEstado'])->name('estado');
    Route::post('/{id}/comentar',          [TicketsController::class, 'comentar'])->name('comentar');
    Route::patch('/{id}/asignar',          [TicketsController::class, 'asignar'])->name('asignar');
    Route::get('/api/conteo',              [TicketsController::class, 'conteo'])->name('conteo');
});
