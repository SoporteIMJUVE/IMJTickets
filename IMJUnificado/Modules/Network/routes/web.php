<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('network')->name('network.')->group(function () {
    Route::get('/', function () {
        $rangos = \DB::table('cat_rangos_ips')->orderBy('area_nombre')->get();
        $ipsAll = \DB::table('inventario_ips_completo')->orderBy('departamento_pestana')->orderBy('ip')->get();

        $totalIps   = \DB::table('inventario_ips_completo')->count();
        $ipsEnUso   = \DB::table('inventario_ips_completo')->where('estatus', 'Ocupada')->count();
        $alertas    = $rangos->filter(function ($r) {
            return $r->capacidad_total > 0 && ($r->ocupadas / $r->capacidad_total) > 0.90;
        })->count();

        return view('network::index', compact(
            'rangos', 'ipsAll', 'totalIps', 'ipsEnUso', 'alertas'
        ));
    })->name('index');
});
