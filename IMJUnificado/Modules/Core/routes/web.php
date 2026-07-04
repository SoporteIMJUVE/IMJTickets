<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;

Route::middleware(['auth'])->group(function () {
    Route::get('/', fn() => redirect()->route('dashboard'))->name('home');
    Route::get('/dashboard', function () {
        return view('core::dashboard', [
            'totalEmpleados' => \DB::table('empleados')->where('activo', true)->count(),
            'totalActivos'   => \DB::table('inventario_equipos')->count(),
            'ticketsAbiertos'=> \DB::table('tickets')->where('estado', 0)->count(),
            'ipsEnUso'       => \DB::table('inventario_ips_completo')->where('estatus', 'Ocupada')->count(),
            'ticketsRecientes' => \DB::table('tickets')->latest()->limit(5)->get(),
        ]);
    })->name('dashboard');
    Route::post('/logout', [CoreController::class, 'logout'])->name('logout');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [CoreController::class, 'loginForm'])->name('login');
    Route::post('/login', [CoreController::class, 'login'])->name('login.post');
});
