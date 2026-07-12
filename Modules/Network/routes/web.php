<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('network')->name('network.')->group(function () {
    Route::get('/exportar', function (\Illuminate\Http\Request $request) {
        return (new \App\Exports\IpsExporter())->download($request->all());
    })->name('exportar');

    Route::get('/', function () {
        $ipsAll = \DB::table('inventario_ips_completo')
            ->orderBy('departamento_pestana')
            ->orderBy('ip')
            ->get();

        // cat_rangos_ips.siglas y .ocupadas están vacíos en el dump;
        // se calculan en tiempo real comparando rangos de IP.
        $rangos = \DB::table('cat_rangos_ips')->orderBy('area_nombre')->get()
            ->map(function ($rango) use ($ipsAll) {
                $ini = ip2long(trim($rango->ip_inicial ?? ''));
                $fin = ip2long(trim($rango->ip_final ?? ''));

                if ($ini === false || $fin === false) {
                    $rango->ocupadas_real = 0;
                    $rango->siglas_real   = '';
                    return $rango;
                }

                $enRango = $ipsAll->filter(function ($ip) use ($ini, $fin) {
                    $n = ip2long(trim($ip->ip ?? ''));
                    return $n !== false && $n >= $ini && $n <= $fin;
                });

                $rango->ocupadas_real = $enRango
                    ->filter(fn($ip) => strtolower($ip->estatus ?? '') === 'ocupada')
                    ->count();
                $rango->siglas_real   = $enRango->first()?->departamento_pestana ?? '';
                return $rango;
            });

        $totalIps = \DB::table('inventario_ips_completo')->count();
        $ipsEnUso = \DB::table('inventario_ips_completo')->where('estatus', 'Ocupada')->count();
        $alertas  = $rangos->filter(fn($r) =>
            ($r->capacidad_total ?: 0) > 0 && ($r->ocupadas_real / $r->capacidad_total) > 0.90
        )->count();

        return view('network::index', compact(
            'rangos', 'ipsAll', 'totalIps', 'ipsEnUso', 'alertas'
        ));
    })->name('index');
});
