<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('impresoras')->name('impresoras.')->group(function () {
    Route::get('/', fn() => view('impresoras::index'))->name('index');
});
