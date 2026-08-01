<?php

namespace Modules\Network\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\IpAssigner;
use App\Support\KardexMovimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NetworkController extends Controller
{
    // Los 12 permisos reales de inventario_ips_completo — nada inventado.
    private const PERMISOS = [
        'youtube', 'vimeo', 'spotify', 'otros_streaming', 'facebook', 'tiktok',
        'instagram', 'whatsapp_web', 'otra_red_social', 'sitios_gub', 'noticias', 'otro_permiso',
    ];

    public function index()
    {
        $ipsAll = DB::table('inventario_ips_completo')
            ->orderBy('departamento_pestana')
            ->orderBy('ip')
            ->get();

        // Lookup FK-linked device info per ip_id (FK wins over stale legacy text)
        $equipoPorIpId = DB::table('inventario_equipos as eq')
            ->leftJoin('users as u', 'eq.user_id', '=', 'u.id')
            ->whereNotNull('eq.ip_id')
            ->select(
                'eq.ip_id',
                'eq.tipo',
                'eq.cpu_marca',
                'eq.cpu_serie',
                DB::raw("NULLIF(TRIM(COALESCE(u.name,'') || ' ' || COALESCE(u.apellido_paterno,'')), '') as responsable")
            )
            ->get()
            ->keyBy('ip_id');

        $impresoraPorIpId = DB::table('impresoras as imp')
            ->leftJoin('users as u', 'imp.user_id', '=', 'u.id')
            ->whereNotNull('imp.ip_id')
            ->select(
                'imp.ip_id',
                'imp.marca',
                'imp.serie',
                DB::raw("NULLIF(TRIM(COALESCE(u.name,'') || ' ' || COALESCE(u.apellido_paterno,'')), '') as responsable")
            )
            ->get()
            ->keyBy('ip_id');

        $ipsAll = $ipsAll->map(function ($ip) use ($equipoPorIpId, $impresoraPorIpId) {
            $eq  = $equipoPorIpId[$ip->id]  ?? null;
            $imp = $impresoraPorIpId[$ip->id] ?? null;
            $ip->responsable_fk = $eq?->responsable ?? $imp?->responsable ?? null;
            $ip->activo_serie   = $eq?->cpu_serie ?? $imp?->serie ?? null;
            $ip->tipo_display   = $eq?->tipo ?? ($imp ? 'Impresora' : null) ?? $ip->tipo_equipo ?? null;
            $ip->marca_display  = $eq?->cpu_marca ?? $imp?->marca ?? $ip->marca ?? null;
            return $ip;
        });

        // cat_rangos_ips.siglas y .ocupadas están vacíos en el dump;
        // se calculan en tiempo real comparando rangos de IP.
        $rangos = DB::table('cat_rangos_ips')->orderBy('area_nombre')->get()
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
                    ->filter(fn ($ip) => strtolower($ip->estatus ?? '') === 'ocupada')
                    ->count();
                $rango->siglas_real   = $enRango->first()?->departamento_pestana ?? '';
                return $rango;
            });

        $totalIps = DB::table('inventario_ips_completo')->count();
        $ipsEnUso = DB::table('inventario_ips_completo')->where('estatus', 'Ocupada')->count();
        $alertas  = $rangos->filter(fn ($r) =>
            ($r->capacidad_total ?: 0) > 0 && ($r->ocupadas_real / $r->capacidad_total) > 0.90
        )->count();

        return view('network::index', compact('rangos', 'ipsAll', 'totalIps', 'ipsEnUso', 'alertas'));
    }

    /** ¿Este valor de permiso (texto sucio: 'Sí'/'SÍ'/'No'/'NO'/'') cuenta como activo? */
    private static function permisoActivo(?string $valor): bool
    {
        $v = mb_strtolower(trim($valor ?? ''));
        return in_array($v, ['si', 'sí', 'yes'], true);
    }

    public function detalle(int $id)
    {
        $ip = DB::table('inventario_ips_completo')->where('id', $id)->first();
        abort_if(!$ip, 404);

        $permisos = [];
        foreach (self::PERMISOS as $campo) {
            $permisos[$campo] = self::permisoActivo($ip->{$campo});
        }

        $ocupante = null;
        if (strtolower($ip->estatus ?? '') === 'ocupada') {
            $encontrado = IpAssigner::findOccupant($id);
            if ($encontrado) {
                if ($encontrado->tabla === 'inventario_equipos') {
                    $eq = DB::table('inventario_equipos')->where('id', $encontrado->id)->first();
                    $responsable = $eq->user_id
                        ? DB::table('users')->where('id', $eq->user_id)->value('name')
                        : null;
                    $ocupante = [
                        'tabla'       => 'inventario_equipos',
                        'id'          => $eq->id,
                        'descripcion' => trim(($eq->tipo ?? '') . ' — ' . ($eq->cpu_serie ?? 'sin serie')),
                        'responsable' => $responsable,
                    ];
                } else {
                    $imp = DB::table('impresoras')->where('id_impresora', $encontrado->id)->first();
                    $responsable = $imp->user_id
                        ? DB::table('users')->where('id', $imp->user_id)->value('name')
                        : null;
                    $ocupante = [
                        'tabla'       => 'impresoras',
                        'id'          => $imp->id_impresora,
                        'descripcion' => trim('Impresora — ' . ($imp->serie ?: 'sin serie')),
                        'responsable' => $responsable,
                    ];
                }
            }
        }

        $equiposDisponibles = DB::table('inventario_equipos')
            ->select('id', 'tipo', 'cpu_serie', 'nombre_usuario')
            ->when($ocupante && $ocupante['tabla'] === 'inventario_equipos', fn ($q) => $q->where('id', '!=', $ocupante['id']))
            ->orderBy('tipo')
            ->get();

        return response()->json([
            'id'            => $ip->id,
            'ip'            => $ip->ip,
            'usuario'       => $ip->usuario,
            'area'          => $ip->area_excel ?? $ip->departamento_pestana,
            'estatus'       => $ip->estatus,
            'mac'           => $ip->mac,
            'tipo_conexion' => $ip->tipo_conexion,
            'permisos'      => $permisos,
            'ocupante'      => $ocupante,
            'equipos'       => $equiposDisponibles,
        ]);
    }

    public function guardarConfiguracion(Request $request, int $id)
    {
        $validated = $request->validate([
            'mac'           => ['nullable', 'string', 'max:30'],
            'tipo_conexion' => ['required', 'in:ALÁMBRICO,INALÁMBRICO'],
            'permisos'      => ['array'],
            'permisos.*'    => ['in:' . implode(',', self::PERMISOS)],
        ]);

        $datos = [
            'mac'           => $validated['mac'] ?: null,
            'tipo_conexion' => $validated['tipo_conexion'],
            'updated_at'    => now(),
        ];

        $marcados = $validated['permisos'] ?? [];
        foreach (self::PERMISOS as $campo) {
            $datos[$campo] = in_array($campo, $marcados, true) ? 'Sí' : 'No';
        }

        DB::table('inventario_ips_completo')->where('id', $id)->update($datos);

        return response()->json(['ok' => true]);
    }

    public function liberarPorEstado(Request $request, int $id)
    {
        $validated = $request->validate([
            'estado' => ['required', 'in:mantenimiento,baja'],
        ]);

        $ocupante = IpAssigner::findOccupant($id);
        if (!$ocupante) {
            return response()->json(['errors' => ['general' => ['Esta IP no está ocupada.']]], 422);
        }

        $ipRow = DB::table('inventario_ips_completo')->where('id', $id)->first();
        $ipStr = $ipRow->ip ?? '—';

        if ($ocupante->tabla === 'inventario_equipos') {
            $eq = DB::table('inventario_equipos')->where('id', $ocupante->id)->first();
            $serie = $eq->cpu_serie ?? '—';

            DB::table('inventario_equipos')->where('id', $ocupante->id)->update([
                'estado'     => $validated['estado'],
                'updated_at' => now(),
            ]);
            IpAssigner::liberarEquipo($ocupante->id);

            KardexMovimiento::registrar(
                tipo_activo:   'equipo',
                activo_id:     (int) $ocupante->id,
                tipo_evento:   'Liberación IP',
                origen:        $serie,
                destino:       'Sin equipo',
                estado_equipo: 'Libre',
                notas:         "IP: {$ipStr}",
            );

            $tipoEvento   = $validated['estado'] === 'baja' ? 'Baja' : 'Mantenimiento';
            $estadoEquipo = $validated['estado'] === 'baja' ? 'Baja' : 'Mantenimiento';
            KardexMovimiento::registrar(
                tipo_activo:   'equipo',
                activo_id:     (int) $ocupante->id,
                tipo_evento:   $tipoEvento,
                origen:        $eq->area ?? 'Sin área',
                destino:       $tipoEvento === 'Baja' ? 'Proveedor' : null,
                user_from_id:  $eq->user_id ?? null,
                estado_equipo: $estadoEquipo,
                notas:         "Operación ejecutada desde panel Network.",
            );
        } else {
            $imp = DB::table('impresoras')->where('id_impresora', $ocupante->id)->first();
            $serie = $imp->serie ?? '—';

            DB::table('impresoras')->where('id_impresora', $ocupante->id)->update([
                'ip_id'      => null,
                'ip_address' => null,
                'updated_at' => now(),
            ]);
            DB::table('inventario_ips_completo')->where('id', $id)->update([
                'estatus'    => 'Libre',
                'updated_at' => now(),
            ]);

            KardexMovimiento::registrar(
                tipo_activo:   'impresora',
                activo_id:     (int) $ocupante->id,
                tipo_evento:   'Liberación IP',
                origen:        $serie,
                destino:       'Sin equipo',
                estado_equipo: 'Libre',
                notas:         "IP: {$ipStr}",
            );
        }

        return response()->json(['ok' => true]);
    }

    public function liberarPorSwitch(Request $request, int $id)
    {
        $validated = $request->validate([
            'equipo_id' => ['required', 'integer', 'exists:inventario_equipos,id'],
        ]);

        $ipRow = DB::table('inventario_ips_completo')->where('id', $id)->first();
        abort_if(!$ipRow, 404);
        $ipStr = $ipRow->ip;

        $ocupante = IpAssigner::findOccupant($id);
        if ($ocupante && $ocupante->tabla === 'inventario_equipos' && (int) $ocupante->id === (int) $validated['equipo_id']) {
            return response()->json(['errors' => ['equipo_id' => ['Ese ya es el equipo actual.']]], 422);
        }

        // Serie del equipo origen (el que pierde la IP)
        $serieOrigen = 'Sin equipo';
        $activo_origen_id   = null;
        $activo_origen_tipo = 'equipo';
        if ($ocupante) {
            $tablaVieja   = $ocupante->tabla;
            $pkVieja      = $tablaVieja === 'inventario_equipos' ? 'id' : 'id_impresora';
            $campoIpVieja = $tablaVieja === 'inventario_equipos' ? 'ipv4' : 'ip_address';
            $filaVieja    = DB::table($tablaVieja)->where($pkVieja, $ocupante->id)->first();
            $serieOrigen  = $tablaVieja === 'inventario_equipos'
                ? ($filaVieja->cpu_serie ?? '—')
                : ($filaVieja->serie ?? '—');
            $activo_origen_id   = (int) $ocupante->id;
            $activo_origen_tipo = $tablaVieja === 'inventario_equipos' ? 'equipo' : 'impresora';

            DB::table($tablaVieja)->where($pkVieja, $ocupante->id)->update([
                'ip_id'        => null,
                $campoIpVieja  => null,
                'updated_at'   => now(),
            ]);
        }

        // Serie del equipo destino (el que recibe la IP)
        $equipoDestino = DB::table('inventario_equipos')->where('id', $validated['equipo_id'])->first();
        $serieDestino  = $equipoDestino->cpu_serie ?? '—';
        $teniaIp       = $equipoDestino->ip_id && (int) $equipoDestino->ip_id !== $id;

        if ($teniaIp) {
            DB::table('inventario_ips_completo')->where('id', $equipoDestino->ip_id)->update([
                'estatus'    => 'Libre',
                'updated_at' => now(),
            ]);
        }

        DB::table('inventario_equipos')->where('id', $validated['equipo_id'])->update([
            'ip_id'      => $id,
            'ipv4'       => $ipStr,
            'updated_at' => now(),
        ]);

        DB::table('inventario_ips_completo')->where('id', $id)->update([
            'estatus'    => 'Ocupada',
            'updated_at' => now(),
        ]);

        // Evento: el equipo origen pierde la IP
        if ($activo_origen_id) {
            KardexMovimiento::registrar(
                tipo_activo:   $activo_origen_tipo,
                activo_id:     $activo_origen_id,
                tipo_evento:   'Liberación IP',
                origen:        $serieOrigen,
                destino:       $serieDestino,
                estado_equipo: 'Libre',
                notas:         "IP: {$ipStr} — liberada por switcheo",
            );
        }

        // Evento: el equipo destino recibe la IP
        KardexMovimiento::registrar(
            tipo_activo:   'equipo',
            activo_id:     (int) $validated['equipo_id'],
            tipo_evento:   $teniaIp ? 'Cambio IP' : 'Asignación IP',
            origen:        $serieOrigen,
            destino:       $serieDestino,
            estado_equipo: 'Ocupada',
            notas:         "IP: {$ipStr}",
        );

        return response()->json(['ok' => true]);
    }
}
