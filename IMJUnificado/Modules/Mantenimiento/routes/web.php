<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('mantenimiento')->name('mantenimiento.')->group(function () {
    Route::get('/', fn() => view('mantenimiento::index'))->name('index');
});
