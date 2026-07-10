<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;

class IpsExporter extends BaseExporter
{
    public function headers(): array
    {
        return [
            'IP Address', 'Usuario Asignado', 'Dispositivo',
            'Estado', 'Tipo Conexión', 'Permisos de Navegación',
        ];
    }

    public function rows(array $filters): array
    {
        $query = DB::table('inventario_ips_completo')
            ->leftJoin('empleados', 'inventario_ips_completo.id_empleado', '=', 'empleados.id_empleado')
            ->select(
                'inventario_ips_completo.*',
                DB::raw("NULLIF(TRIM(COALESCE(empleados.nombre,'') || ' ' || COALESCE(empleados.apellido_paterno,'')), '') as empleado_nombre")
            )
            ->orderBy('inventario_ips_completo.departamento_pestana')
            ->orderBy('inventario_ips_completo.ip');

        if (!empty($filters['net-filter-estatus'])) {
            $query->where(DB::raw('LOWER(inventario_ips_completo.estatus)'), $filters['net-filter-estatus']);
        }

        if (!empty($filters['net-filter-area'])) {
            $area = $filters['net-filter-area'];
            $query->where(DB::raw('LOWER(inventario_ips_completo.departamento_pestana)'), $area);
        }

        $columnas_permisos = [
            'youtube'        => 'YouTube',
            'vimeo'          => 'Vimeo',
            'spotify'        => 'Spotify',
            'facebook'       => 'Facebook',
            'tiktok'         => 'TikTok',
            'instagram'      => 'Instagram',
            'whatsapp_web'   => 'WhatsApp Web',
            'otra_red_social'=> 'Otra Red Social',
            'sitios_gub'     => 'Sitios Gov.',
            'noticias'       => 'Noticias',
            'otro_permiso'   => 'Otro',
        ];

        $rows = [];
        foreach ($query->get() as $ip) {
            $usuario     = $ip->empleado_nombre ?: ($ip->usuario ?? '—');
            $dispositivo = trim(($ip->marca ?? '') . ' ' . ($ip->modelo ?? '')) ?: ($ip->tipo_equipo ?? '—');

            $permisos = [];
            foreach ($columnas_permisos as $col => $label) {
                $val = strtolower(trim($ip->$col ?? ''));
                if ($val === 'si' || $val === 'sí' || $val === 'yes') {
                    $permisos[] = $label;
                }
            }

            $rows[] = [
                $ip->ip ?? '—',
                $usuario,
                $dispositivo,
                $ip->estatus ?? '—',
                $ip->tipo_conexion ?? '—',
                $permisos ? implode(', ', $permisos) : 'Sin permisos especiales',
            ];
        }

        return $rows;
    }

    public function filename(): string
    {
        return 'inventario_ips';
    }
}
