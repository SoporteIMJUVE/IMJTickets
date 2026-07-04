<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('tickets')->name('tickets.')->group(function () {
    Route::get('/', fn() => view('tickets::index'))->name('index');
});
