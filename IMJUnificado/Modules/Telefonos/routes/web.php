<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('telefonos')->name('telefonos.')->group(function () {
    Route::get('/', fn() => view('telefonos::index'))->name('index');
});
