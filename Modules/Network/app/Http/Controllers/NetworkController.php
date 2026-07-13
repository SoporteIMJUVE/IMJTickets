<?php

namespace Modules\Network\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Support\IpAssigner;
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

        if ($ocupante->tabla === 'inventario_equipos') {
            DB::table('inventario_equipos')->where('id', $ocupante->id)->update([
                'estado'     => $validated['estado'],
                'updated_at' => now(),
            ]);
            IpAssigner::liberarEquipo($ocupante->id);
        } else {
            // impresoras no tiene columna de estado — solo se desliga la IP.
            DB::table('impresoras')->where('id_impresora', $ocupante->id)->update([
                'ip_id'      => null,
                'ip_address' => null,
                'updated_at' => now(),
            ]);
            DB::table('inventario_ips_completo')->where('id', $id)->update([
                'estatus'    => 'Libre',
                'updated_at' => now(),
            ]);
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

        $ocupante = IpAssigner::findOccupant($id);
        if ($ocupante && $ocupante->tabla === 'inventario_equipos' && (int) $ocupante->id === (int) $validated['equipo_id']) {
            return response()->json(['errors' => ['equipo_id' => ['Ese ya es el equipo actual.']]], 422);
        }

        if ($ocupante) {
            $tablaVieja = $ocupante->tabla;
            $pkVieja    = $tablaVieja === 'inventario_equipos' ? 'id' : 'id_impresora';
            $campoIpVieja = $tablaVieja === 'inventario_equipos' ? 'ipv4' : 'ip_address';
            DB::table($tablaVieja)->where($pkVieja, $ocupante->id)->update([
                'ip_id'        => null,
                $campoIpVieja  => null,
                'updated_at'   => now(),
            ]);
        }

        // El equipo destino podría ya tener otra IP asignada — esa IP vieja
        // se queda huérfana si no la liberamos también.
        $equipoDestino = DB::table('inventario_equipos')->where('id', $validated['equipo_id'])->first();
        if ($equipoDestino->ip_id && (int) $equipoDestino->ip_id !== $id) {
            DB::table('inventario_ips_completo')->where('id', $equipoDestino->ip_id)->update([
                'estatus'    => 'Libre',
                'updated_at' => now(),
            ]);
        }

        DB::table('inventario_equipos')->where('id', $validated['equipo_id'])->update([
            'ip_id'      => $id,
            'ipv4'       => $ipRow->ip,
            'updated_at' => now(),
        ]);

        DB::table('inventario_ips_completo')->where('id', $id)->update([
            'estatus'    => 'Ocupada',
            'updated_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }
}
