<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * `inventario_ips_completo` es el registro maestro de IPs (una sola fila por
 * IP). Este helper resuelve una IP a su fila maestra (creándola si hace
 * falta) y verifica que ningún otro dispositivo la tenga ya asignada, para
 * que la restricción de unicidad a nivel BD nunca se dispare como un error
 * crudo sin contexto.
 */
class IpAssigner
{
    private const TABLAS_CON_IP = [
        'inventario_equipos' => 'id',
        'impresoras'         => 'id_impresora',
    ];

    public static function resolveId(string $ip): int
    {
        $existente = DB::table('inventario_ips_completo')->where('ip', $ip)->first();
        if ($existente) {
            return $existente->id;
        }

        return DB::table('inventario_ips_completo')->insertGetId([
            'ip'         => $ip,
            'estatus'    => 'Ocupada',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * ¿Ese ip_id ya pertenece a otro dispositivo (equipo o impresora)?
     * $exceptId excluye la fila que se está guardando (para updates).
     */
    public static function assignedElsewhere(int $ipId, string $tabla, ?int $exceptId = null): bool
    {
        foreach (self::TABLAS_CON_IP as $tablaDispositivo => $columnaPk) {
            $query = DB::table($tablaDispositivo)->where('ip_id', $ipId);

            if ($tablaDispositivo === $tabla && $exceptId !== null) {
                $query->where($columnaPk, '<>', $exceptId);
            }

            if ($query->exists()) {
                return true;
            }
        }

        return false;
    }

    /**
     * ¿Quién tiene hoy esa IP? Devuelve {tabla, id, user_id} de la fila que
     * la ocupa, o null si está libre. A diferencia de assignedElsewhere(),
     * el llamador decide qué hacer con el resultado (ej. permitir un
     * switcheo si el ocupante es la misma persona que se está asignando).
     */
    public static function findOccupant(int $ipId): ?object
    {
        foreach (self::TABLAS_CON_IP as $tablaDispositivo => $columnaPk) {
            $fila = DB::table($tablaDispositivo)->where('ip_id', $ipId)->first();
            if ($fila) {
                return (object) [
                    'tabla'   => $tablaDispositivo,
                    'id'      => $fila->{$columnaPk},
                    'user_id' => $fila->user_id,
                ];
            }
        }

        return null;
    }

    /**
     * Libera la IP de un equipo (ej. al mandarlo a mantenimiento o baja):
     * limpia su ip_id/ipv4 y devuelve la fila maestra a 'Libre'. No hace
     * nada si el equipo no existe o no tenía IP asignada.
     */
    public static function liberarEquipo(int $equipoId): void
    {
        $eq = DB::table('inventario_equipos')->where('id', $equipoId)->first();
        if (!$eq || !$eq->ip_id) {
            return;
        }

        DB::table('inventario_equipos')->where('id', $equipoId)->update([
            'ip_id'      => null,
            'ipv4'       => null,
            'updated_at' => now(),
        ]);

        DB::table('inventario_ips_completo')->where('id', $eq->ip_id)->update([
            'estatus'    => 'Libre',
            'updated_at' => now(),
        ]);
    }
}
