<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Población de FKs y campos de red que quedaron vacíos en la importación original:
 *
 *  1. inventario_equipos.id_empleado  via nombre_usuario ↔ empleados.(nombre+apellido_paterno)
 *  2. inventario_equipos.ipv4 + mac   via cpu_serie ↔ inventario_ips_completo.serie
 *  3. inventario_ips_completo.id_empleado via la cadena inventario_ips_completo.serie
 *                                           → inventario_equipos.cpu_serie
 *                                           → inventario_equipos.id_empleado
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Vincular inventario_equipos.id_empleado por nombre ──────────────
        $empleados = DB::table('empleados')
            ->whereNotNull('nombre')
            ->whereNotNull('apellido_paterno')
            ->get(['id_empleado', 'nombre', 'apellido_paterno']);

        $equiposSinFK = DB::table('inventario_equipos')
            ->whereNull('id_empleado')
            ->whereNotNull('nombre_usuario')
            ->get(['id', 'nombre_usuario']);

        $vinculados = 0;
        foreach ($equiposSinFK as $eq) {
            $nu = mb_strtolower(trim($eq->nombre_usuario));
            foreach ($empleados as $emp) {
                $nombre   = mb_strtolower(trim($emp->nombre));
                $apellido = mb_strtolower(trim($emp->apellido_paterno));
                if ($nombre && $apellido
                    && str_contains($nu, $nombre)
                    && str_contains($nu, $apellido)) {
                    DB::table('inventario_equipos')
                        ->where('id', $eq->id)
                        ->update(['id_empleado' => $emp->id_empleado, 'updated_at' => now()]);
                    $vinculados++;
                    break;
                }
            }
        }
        echo "  inventario_equipos.id_empleado: {$vinculados} registros vinculados.\n";

        // ── 2. Poblar inventario_equipos.ipv4 y mac desde inventario_ips_completo ──
        // SQLite no soporta UPDATE...FROM, usamos subqueries correlacionadas.
        DB::statement("
            UPDATE inventario_equipos
            SET ipv4 = (
                SELECT ip
                FROM inventario_ips_completo ips
                WHERE LOWER(TRIM(ips.serie)) = LOWER(TRIM(inventario_equipos.cpu_serie))
                  AND ips.ip IS NOT NULL
                LIMIT 1
            )
            WHERE ipv4 IS NULL
              AND EXISTS (
                SELECT 1 FROM inventario_ips_completo ips
                WHERE LOWER(TRIM(ips.serie)) = LOWER(TRIM(inventario_equipos.cpu_serie))
              )
        ");

        DB::statement("
            UPDATE inventario_equipos
            SET mac = (
                SELECT mac
                FROM inventario_ips_completo ips
                WHERE LOWER(TRIM(ips.serie)) = LOWER(TRIM(inventario_equipos.cpu_serie))
                  AND ips.mac IS NOT NULL
                  AND TRIM(ips.mac) != ''
                  AND TRIM(ips.mac) != '/'
                LIMIT 1
            )
            WHERE mac IS NULL
              AND EXISTS (
                SELECT 1 FROM inventario_ips_completo ips
                WHERE LOWER(TRIM(ips.serie)) = LOWER(TRIM(inventario_equipos.cpu_serie))
                  AND ips.mac IS NOT NULL
                  AND TRIM(ips.mac) != '' AND TRIM(ips.mac) != '/'
              )
        ");

        $conIp = DB::table('inventario_equipos')->whereNotNull('ipv4')->count();
        echo "  inventario_equipos.ipv4 poblado: {$conIp} registros con IP.\n";

        // ── 3. Vincular inventario_ips_completo.id_empleado via la cadena de serie ──
        DB::statement("
            UPDATE inventario_ips_completo
            SET id_empleado = (
                SELECT ie.id_empleado
                FROM inventario_equipos ie
                WHERE LOWER(TRIM(inventario_ips_completo.serie)) = LOWER(TRIM(ie.cpu_serie))
                  AND ie.id_empleado IS NOT NULL
                LIMIT 1
            )
            WHERE id_empleado IS NULL
              AND EXISTS (
                SELECT 1 FROM inventario_equipos ie
                WHERE LOWER(TRIM(inventario_ips_completo.serie)) = LOWER(TRIM(ie.cpu_serie))
                  AND ie.id_empleado IS NOT NULL
              )
        ");

        $ipsVinculadas = DB::table('inventario_ips_completo')->whereNotNull('id_empleado')->count();
        echo "  inventario_ips_completo.id_empleado: {$ipsVinculadas} registros vinculados.\n";
    }

    public function down(): void
    {
        // Revertir: limpiar los FKs y el campo ipv4 que poblamos
        DB::table('inventario_equipos')->update(['id_empleado' => null, 'ipv4' => null, 'mac' => null]);
        DB::table('inventario_ips_completo')->update(['id_empleado' => null]);
    }
};
