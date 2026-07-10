<?php

use Illuminate\Support\Facades\Route;
use Modules\Core\Http\Controllers\CoreController;

Route::middleware(['auth'])->group(function () {
    Route::get('/', fn() => redirect()->route('dashboard'))->name('home');

    // ── Omnibuscador global ───────────────────────────────────────────────────
    Route::get('/search', function (\Illuminate\Http\Request $request) {
        $q = trim($request->input('q', ''));
        if (mb_strlen($q) < 2) return response()->json([]);

        $like   = '%' . addcslashes($q, '%_') . '%';
        $groups = [];

        // ── Empleados ─────────────────────────────────────────────────────────
        $condEmp = fn ($b) => $b
            ->whereRaw("LOWER(nombre) LIKE LOWER(?)",                    [$like])
            ->orWhereRaw("LOWER(apellido_paterno) LIKE LOWER(?)",        [$like])
            ->orWhereRaw("LOWER(apellido_materno) LIKE LOWER(?)",        [$like])
            ->orWhereRaw("LOWER(COALESCE(puesto,'')) LIKE LOWER(?)",     [$like])
            ->orWhereRaw("LOWER(COALESCE(correo,'')) LIKE LOWER(?)",     [$like]);

        $totalEmp = \DB::table('empleados')->where('activo', true)->where($condEmp)->count();
        if ($totalEmp > 0) {
            $rows = \DB::table('empleados')->where('activo', true)->where($condEmp)
                        ->select('nombre', 'apellido_paterno', 'puesto')->limit(4)->get();
            $groups[] = [
                'modulo' => 'Usuarios',
                'icon'   => 'group',
                'url'    => route('crm.index'),
                'total'  => $totalEmp,
                'items'  => $rows->map(fn ($e) => [
                    'label' => trim("{$e->nombre} {$e->apellido_paterno}"),
                    'sub'   => $e->puesto ?: null,
                    'url'   => route('crm.index'),
                ])->all(),
            ];
        }

        // ── Inventario ────────────────────────────────────────────────────────
        $condEq = fn ($b) => $b
            ->whereRaw("LOWER(COALESCE(nombre_equipo,'')) LIKE LOWER(?)", [$like])
            ->orWhereRaw("LOWER(COALESCE(cpu_serie,'')) LIKE LOWER(?)",   [$like])
            ->orWhereRaw("LOWER(COALESCE(cpu_marca,'')) LIKE LOWER(?)",   [$like])
            ->orWhereRaw("LOWER(COALESCE(cpu_modelo,'')) LIKE LOWER(?)",  [$like])
            ->orWhereRaw("CAST(COALESCE(num_inventario,0) AS TEXT) LIKE ?", [$like]);

        $totalEq = \DB::table('inventario_equipos')->where($condEq)->count();
        if ($totalEq > 0) {
            $rows = \DB::table('inventario_equipos')->where($condEq)
                        ->select('tipo', 'nombre_equipo', 'cpu_marca', 'cpu_modelo')->limit(4)->get();
            $groups[] = [
                'modulo' => 'Inventario',
                'icon'   => 'inventory_2',
                'url'    => route('kardex.index'),
                'total'  => $totalEq,
                'items'  => $rows->map(fn ($e) => [
                    'label' => $e->nombre_equipo ?: trim("{$e->cpu_marca} {$e->cpu_modelo}") ?: 'Equipo',
                    'sub'   => $e->tipo,
                    'url'   => route('kardex.index'),
                ])->all(),
            ];
        }

        // ── Red e IPs ─────────────────────────────────────────────────────────
        if (\Schema::hasTable('inventario_ips_completo')) {
            $condIp = fn ($b) => $b
                ->whereRaw("ip::text ILIKE ?",                          [$like])
                ->orWhereRaw("LOWER(COALESCE(usuario,'')) LIKE LOWER(?)", [$like]);

            $totalIp = \DB::table('inventario_ips_completo')->where($condIp)->count();
            if ($totalIp > 0) {
                $rows = \DB::table('inventario_ips_completo')->where($condIp)
                            ->select('ip', 'usuario', 'estatus')->limit(4)->get();
                $groups[] = [
                    'modulo' => 'Red e IPs',
                    'icon'   => 'lan',
                    'url'    => route('network.index'),
                    'total'  => $totalIp,
                    'items'  => $rows->map(fn ($i) => [
                        'label' => $i->ip,
                        'sub'   => $i->usuario ?: ($i->estatus ?: null),
                        'url'   => route('network.index'),
                    ])->all(),
                ];
            }
        }

        // ── Tickets ───────────────────────────────────────────────────────────
        if (\Schema::hasTable('tickets')) {
            $condTk = fn ($b) => $b
                ->whereRaw("LOWER(COALESCE(descripcion,'')) LIKE LOWER(?)",    [$like])
                ->orWhereRaw("LOWER(COALESCE(tipo,'')) LIKE LOWER(?)",         [$like])
                ->orWhereRaw("LOWER(COALESCE(area,'')) LIKE LOWER(?)",         [$like])
                ->orWhereRaw("LOWER(COALESCE(nombre_reporta,'')) LIKE LOWER(?)", [$like]);

            $totalTk = \DB::table('tickets')->where($condTk)->count();
            if ($totalTk > 0) {
                $rows = \DB::table('tickets')->where($condTk)
                            ->select('id', 'tipo', 'descripcion')->latest()->limit(4)->get();
                $groups[] = [
                    'modulo' => 'Tickets',
                    'icon'   => 'description',
                    'url'    => route('tickets.index'),
                    'total'  => $totalTk,
                    'items'  => $rows->map(fn ($t) => [
                        'label' => mb_strimwidth($t->descripcion ?? $t->tipo ?? "#$t->id", 0, 55, '…'),
                        'sub'   => $t->tipo,
                        'url'   => route('tickets.index'),
                    ])->all(),
                ];
            }
        }

        return response()->json($groups);
    })->name('search');

    Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
        $searchQuery   = trim($request->input('q', ''));
        $searchResults = null;

        if (mb_strlen($searchQuery) >= 2) {
            $like   = '%' . addcslashes($searchQuery, '%_') . '%';
            $groups = [];

            // Empleados — nombre, apellidos, puesto, correo, departamento y extensión
            $condEmp = fn ($b) => $b
                ->whereRaw("LOWER(e.nombre) LIKE LOWER(?)",                       [$like])
                ->orWhereRaw("LOWER(e.apellido_paterno) LIKE LOWER(?)",           [$like])
                ->orWhereRaw("LOWER(e.apellido_materno) LIKE LOWER(?)",           [$like])
                ->orWhereRaw("LOWER(COALESCE(e.puesto,'')) LIKE LOWER(?)",        [$like])
                ->orWhereRaw("LOWER(COALESCE(e.correo,'')) LIKE LOWER(?)",        [$like])
                ->orWhereRaw("LOWER(COALESCE(d.nombre,'')) LIKE LOWER(?)",        [$like])
                ->orWhereRaw("CAST(COALESCE(t.extension,0) AS TEXT) LIKE ?",      [$like]);
            $baseEmp = \DB::table('empleados as e')
                ->leftJoin('departamentos as d', 'd.id_departamento', '=', 'e.id_departamento')
                ->leftJoin('telefonos as t', 't.id_empleado', '=', 'e.id_empleado')
                ->where('e.activo', true)
                ->distinct();
            $totalEmp = (clone $baseEmp)->where($condEmp)->count('e.id_empleado');
            if ($totalEmp > 0) {
                $rows = (clone $baseEmp)->where($condEmp)
                            ->select('e.id_empleado', 'e.nombre', 'e.apellido_paterno', 'e.puesto', 'd.nombre as departamento', 't.extension')
                            ->limit(4)->get();
                $groups[] = [
                    'modulo' => 'Usuarios', 'icon' => 'group',
                    'url'    => route('crm.index'), 'total' => $totalEmp,
                    'items'  => $rows->map(fn ($e) => [
                        'label' => trim("{$e->nombre} {$e->apellido_paterno}"),
                        'sub'   => implode(' · ', array_filter([
                            $e->puesto ?: null,
                            $e->extension ? "Ext. {$e->extension}" : null,
                        ])) ?: ($e->departamento ?: null),
                        'url'   => route('crm.index') . '?open=' . $e->id_empleado,
                    ])->all(),
                ];
            }

            // Inventario de equipos — todos los campos de texto y series
            $condEq = fn ($b) => $b
                ->whereRaw("LOWER(COALESCE(nombre_usuario,'')) LIKE LOWER(?)",    [$like])
                ->orWhereRaw("LOWER(COALESCE(nombre_equipo,'')) LIKE LOWER(?)",   [$like])
                ->orWhereRaw("LOWER(COALESCE(area,'')) LIKE LOWER(?)",            [$like])
                ->orWhereRaw("LOWER(COALESCE(cpu_marca,'')) LIKE LOWER(?)",       [$like])
                ->orWhereRaw("LOWER(COALESCE(cpu_modelo,'')) LIKE LOWER(?)",      [$like])
                ->orWhereRaw("LOWER(COALESCE(cpu_serie,'')) LIKE LOWER(?)",       [$like])
                ->orWhereRaw("LOWER(COALESCE(num_inventario,'')) LIKE LOWER(?)",  [$like])
                ->orWhereRaw("LOWER(COALESCE(monitor_marca,'')) LIKE LOWER(?)",   [$like])
                ->orWhereRaw("LOWER(COALESCE(monitor_modelo,'')) LIKE LOWER(?)",  [$like])
                ->orWhereRaw("LOWER(COALESCE(monitor_serie,'')) LIKE LOWER(?)",   [$like])
                ->orWhereRaw("LOWER(COALESCE(teclado_serie,'')) LIKE LOWER(?)",   [$like])
                ->orWhereRaw("LOWER(COALESCE(mouse_serie,'')) LIKE LOWER(?)",     [$like])
                ->orWhereRaw("LOWER(COALESCE(nobreak_marca,'')) LIKE LOWER(?)",   [$like])
                ->orWhereRaw("LOWER(COALESCE(nobreak_serie,'')) LIKE LOWER(?)",   [$like])
                ->orWhereRaw("LOWER(COALESCE(cargador_serie,'')) LIKE LOWER(?)",  [$like])
                ->orWhereRaw("LOWER(COALESCE(docking_marca,'')) LIKE LOWER(?)",   [$like])
                ->orWhereRaw("LOWER(COALESCE(docking_serie,'')) LIKE LOWER(?)",   [$like])
                ->orWhereRaw("LOWER(COALESCE(ipv4,'')) LIKE LOWER(?)",            [$like])
                ->orWhereRaw("LOWER(COALESCE(ipv4_actual,'')) LIKE LOWER(?)",     [$like])
                ->orWhereRaw("LOWER(COALESCE(mac,'')) LIKE LOWER(?)",             [$like]);
            $totalEq = \DB::table('inventario_equipos')->where($condEq)->count();
            if ($totalEq > 0) {
                $rows = \DB::table('inventario_equipos')->where($condEq)
                            ->select('id', 'tipo', 'nombre_usuario', 'nombre_equipo', 'cpu_marca', 'cpu_modelo', 'cpu_serie', 'num_inventario')->limit(4)->get();
                $groups[] = [
                    'modulo' => 'Inventario', 'icon' => 'inventory_2',
                    'url'    => route('kardex.index'), 'total' => $totalEq,
                    'items'  => $rows->map(fn ($e) => [
                        'label' => $e->nombre_usuario ?: ($e->nombre_equipo ?: trim("{$e->cpu_marca} {$e->cpu_modelo}") ?: 'Equipo'),
                        'sub'   => trim("{$e->tipo} — {$e->cpu_marca} {$e->cpu_modelo}" . ($e->cpu_serie ? " · {$e->cpu_serie}" : '')),
                        'url'   => route('kardex.index') . '?open=' . $e->id,
                    ])->all(),
                ];
            }

            // Red e IPs
            if (\Schema::hasTable('inventario_ips_completo')) {
                $condIp = fn ($b) => $b
                    ->whereRaw("CAST(ip AS TEXT) LIKE ?",                                    [$like])
                    ->orWhereRaw("LOWER(COALESCE(usuario,'')) LIKE LOWER(?)",                [$like])
                    ->orWhereRaw("LOWER(COALESCE(area_excel,'')) LIKE LOWER(?)",             [$like])
                    ->orWhereRaw("LOWER(COALESCE(departamento_pestana,'')) LIKE LOWER(?)",   [$like])
                    ->orWhereRaw("LOWER(COALESCE(tipo_equipo,'')) LIKE LOWER(?)",            [$like])
                    ->orWhereRaw("LOWER(COALESCE(mac,'')) LIKE LOWER(?)",                    [$like]);
                $totalIp = \DB::table('inventario_ips_completo')->where($condIp)->count();
                if ($totalIp > 0) {
                    $rows = \DB::table('inventario_ips_completo')->where($condIp)
                                ->select('ip', 'usuario', 'estatus')->limit(4)->get();
                    $groups[] = [
                        'modulo' => 'Red e IPs', 'icon' => 'lan',
                        'url'    => route('network.index'), 'total' => $totalIp,
                        'items'  => $rows->map(fn ($i) => [
                            'label' => $i->ip,
                            'sub'   => $i->usuario ?: ($i->estatus ?: null),
                            'url'   => route('network.index') . '?open=' . urlencode($i->ip),
                        ])->all(),
                    ];
                }
            }

            // Tickets
            if (\Schema::hasTable('tickets')) {
                $condTk = fn ($b) => $b
                    ->whereRaw("LOWER(COALESCE(descripcion,'')) LIKE LOWER(?)", [$like])
                    ->orWhereRaw("LOWER(COALESCE(tipo,'')) LIKE LOWER(?)",      [$like])
                    ->orWhereRaw("LOWER(COALESCE(area,'')) LIKE LOWER(?)",      [$like])
                    ->orWhereRaw("LOWER(COALESCE(nombre,'')) LIKE LOWER(?)",    [$like])
                    ->orWhereRaw("LOWER(COALESCE(correo,'')) LIKE LOWER(?)",    [$like]);
                $totalTk = \DB::table('tickets')->where($condTk)->count();
                if ($totalTk > 0) {
                    $rows = \DB::table('tickets')->where($condTk)
                                ->select('id', 'tipo', 'descripcion', 'nombre')->latest()->limit(4)->get();
                    $groups[] = [
                        'modulo' => 'Tickets', 'icon' => 'description',
                        'url'    => route('tickets.index'), 'total' => $totalTk,
                        'items'  => $rows->map(fn ($t) => [
                            'label' => mb_strimwidth($t->descripcion ?? $t->tipo ?? "#$t->id", 0, 55, '…'),
                            'sub'   => implode(' · ', array_filter([$t->nombre ?: null, $t->tipo ?: null])),
                            'url'   => route('tickets.index') . '?open=' . $t->id,
                        ])->all(),
                    ];
                }
            }

            $searchResults = $groups;
        }

        return view('core::dashboard', [
            'totalEmpleados'  => \DB::table('empleados')->where('activo', true)->count(),
            'totalActivos'    => \DB::table('inventario_equipos')->count(),
            'ticketsAbiertos' => \DB::table('tickets')->where('estado', 0)->count(),
            'ipsEnUso'        => \DB::table('inventario_ips_completo')->where('estatus', 'Ocupada')->count(),
            'ticketsRecientes'=> \DB::table('tickets')->latest()->limit(5)->get(),
            'searchQuery'     => $searchQuery,
            'searchResults'   => $searchResults,
        ]);
    })->name('dashboard');
    Route::post('/logout', [CoreController::class, 'logout'])->name('logout');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [CoreController::class, 'loginForm'])->name('login');
    Route::post('/login', [CoreController::class, 'login'])->name('login.post');
});
