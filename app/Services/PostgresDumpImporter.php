<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Parsea un dump PostgreSQL en formato COPY (pg_dump estándar)
 * y migra los datos al esquema de IMJUnificado.
 *
 * El formato COPY usa TSV (tab-separated) y \N para NULL.
 * Los booleanos de PostgreSQL llegan como 't' / 'f'.
 */
class PostgresDumpImporter
{
    private string $sqlPath;
    private array  $log = [];

    public function __construct(string $sqlPath)
    {
        $this->sqlPath = $sqlPath;
    }

    public function run(): array
    {
        if (!file_exists($this->sqlPath)) {
            throw new \RuntimeException("Archivo no encontrado: {$this->sqlPath}");
        }

        $content = file_get_contents($this->sqlPath);

        $this->importarDepartamentos($content);
        $this->importarUsuariosComoEmpleados($content);
        $this->importarTelefonos($content);
        $this->importarInventarioEquipos($content);
        $this->importarImpresoras($content);
        $this->importarInsumos($content);
        $this->importarSuministros($content);
        $this->importarRangosIPs($content);

        return $this->log;
    }

    // ─── Parser de bloques COPY ───────────────────────────────────────────────

    /**
     * Extrae filas de un bloque COPY public.tabla (...) FROM stdin;
     * Retorna array de arrays asociativos [col => valor].
     */
    private function parseCopy(string $content, string $tabla): array
    {
        $pattern = '/COPY public\.' . preg_quote($tabla, '/') .
                   '\s*\(([^)]+)\)\s+FROM stdin;\n(.*?)\\\\\./s';

        if (!preg_match($pattern, $content, $m)) {
            $this->log[] = "⚠  No se encontró bloque COPY para '{$tabla}'";
            return [];
        }

        $cols = array_map('trim', explode(',', $m[1]));
        $rows = [];

        foreach (explode("\n", trim($m[2])) as $line) {
            if ($line === '' || $line === '\.') continue;
            $vals = explode("\t", $line);
            $row  = [];
            foreach ($cols as $i => $col) {
                $v = $vals[$i] ?? null;
                // \N = NULL en formato COPY
                $row[$col] = ($v === '\\N' || $v === null) ? null : $v;
            }
            $rows[] = $row;
        }

        return $rows;
    }

    // ─── Convertidores de tipos PostgreSQL ────────────────────────────────────

    private function bool(mixed $v): ?int
    {
        if ($v === null) return null;
        return ($v === 't' || $v === 'true' || $v === '1') ? 1 : 0;
    }

    private function int(mixed $v): ?int
    {
        return $v !== null ? (int) $v : null;
    }

    private function ts(mixed $v): ?string
    {
        if ($v === null) return null;
        // Convierte "2026-06-26 19:42:52.453261" → "2026-06-26 19:42:52"
        return substr($v, 0, 19);
    }

    // ─── Importadores por tabla ────────────────────────────────────────────────

    private function importarDepartamentos(string $content): void
    {
        $filas = $this->parseCopy($content, 'departamentos');
        if (!$filas) return;

        DB::table('departamentos')->truncate();

        $insert = array_map(fn($r) => [
            'id_departamento' => $this->int($r['id_departamento']),
            'nombre'          => $r['nombre'],
            'created_at'      => now(),
            'updated_at'      => now(),
        ], $filas);

        DB::table('departamentos')->insert($insert);
        $this->log[] = "✅ departamentos: {$this->count($filas)} registros";
    }

    private function importarUsuariosComoEmpleados(string $content): void
    {
        $filas = $this->parseCopy($content, 'usuarios');
        if (!$filas) return;

        // No truncar — pueden existir usuarios creados manualmente
        $insertados  = 0;
        $actualizados = 0;

        foreach ($filas as $r) {
            $existente = DB::table('empleados')
                ->where('id_empleado', $this->int($r['id_usuario']))
                ->first();

            $correoRaw = $r['correo'] ? trim($r['correo']) : null;
            $correo    = ($correoRaw && filter_var($correoRaw, FILTER_VALIDATE_EMAIL)) ? $correoRaw : null;

            $data = [
                'nombre'           => $r['nombre'],
                'apellido_paterno' => $r['apellido_paterno'],
                'apellido_materno' => $r['apellido_materno'],
                'puesto'           => $r['puesto'],
                'correo'           => $correo,
                'id_departamento'  => $this->int($r['id_departamento']),
                'activo'           => $this->bool($r['activo']) ?? 1,
                'updated_at'       => now(),
            ];

            if ($existente) {
                DB::table('empleados')->where('id_empleado', $this->int($r['id_usuario']))->update($data);
                $actualizados++;
            } else {
                DB::table('empleados')->insert(array_merge($data, [
                    'id_empleado' => $this->int($r['id_usuario']),
                    'fecha_alta'  => now(),
                    'created_at'  => now(),
                ]));
                $insertados++;
            }
        }

        $this->log[] = "✅ empleados (desde usuarios): {$insertados} nuevos, {$actualizados} actualizados";
    }

    private function importarTelefonos(string $content): void
    {
        $filas = $this->parseCopy($content, 'telefonos');
        if (!$filas) return;

        DB::table('telefonos')->truncate();

        $insert = [];
        foreach ($filas as $r) {
            // Solo importar si tiene usuario asignado y la FK existe
            $idEmpleado = $this->int($r['id_usuario']);
            if ($idEmpleado && !DB::table('empleados')->where('id_empleado', $idEmpleado)->exists()) {
                continue;
            }
            $insert[] = [
                'id_telefono'    => $this->int($r['id_telefono']),
                'numero_general' => $r['numero_general'],
                'extension'      => $r['extension'] ? (int) $r['extension'] : null,
                'id_empleado'    => $idEmpleado,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        if ($insert) DB::table('telefonos')->insert($insert);
        $this->log[] = "✅ telefonos: {$this->count($insert)} registros";
    }

    private function importarInventarioEquipos(string $content): void
    {
        $filas = $this->parseCopy($content, 'inventario_equipos');
        if (!$filas) return;

        DB::table('inventario_equipos')->truncate();

        $insert = [];
        foreach ($filas as $r) {
            $idEmpleado = $this->int($r['id_usuario']);
            $insert[] = [
                'id'             => $this->int($r['id']),
                'tipo'           => $r['tipo'],
                'consecutivo'    => $this->int($r['consecutivo']),
                'num_inventario' => $r['num_inventario'],
                'nombre_equipo'  => $r['nombre_equipo'],
                'nombre_usuario' => $r['nombre_usuario'],
                'perfil'         => $r['perfil'],
                'area'           => $r['area'],
                'cpu_marca'      => $r['cpu_marca'],
                'cpu_modelo'     => $r['cpu_modelo'],
                'cpu_serie'      => $r['cpu_serie'],
                'teclado_serie'  => $r['teclado_serie'],
                'mouse_serie'    => $r['mouse_serie'],
                'monitor_marca'  => $r['monitor_marca'],
                'monitor_modelo' => $r['monitor_modelo'],
                'monitor_serie'  => $r['monitor_serie'],
                'nobreak_marca'  => $r['nobreak_marca'],
                'nobreak_modelo' => $r['nobreak_modelo'],
                'nobreak_serie'  => $r['nobreak_serie'],
                'cargador_serie' => $r['cargador_serie'],
                'docking_marca'  => $r['docking_marca'],
                'docking_modelo' => $r['docking_modelo'],
                'docking_serie'  => $r['docking_serie'],
                'candado'        => $r['candado'],
                'ipv4'           => $r['ipv4'],
                'ipv4_actual'    => $r['ipv4_actual'],
                'mac'            => $r['mac'],
                'responsiva'     => $r['responsiva'],
                'check_entrega'  => $r['check_entrega'],
                'observaciones'  => $r['observaciones'],
                'id_empleado'    => $idEmpleado,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }

        if ($insert) DB::table('inventario_equipos')->insert($insert);
        $this->log[] = "✅ inventario_equipos: {$this->count($insert)} registros";
    }

    private function importarImpresoras(string $content): void
    {
        $filas = $this->parseCopy($content, 'impresoras');
        if (!$filas) return;

        DB::table('impresoras')->truncate();

        $insert = [];
        foreach ($filas as $r) {
            $idEmpleado = $this->int($r['id_usuario']);
            if ($idEmpleado && !DB::table('empleados')->where('id_empleado', $idEmpleado)->exists()) {
                $idEmpleado = null;
            }
            $insert[] = [
                'id_impresora' => $this->int($r['id_impresora']),
                'marca'        => $r['marca'],
                'modelo'       => $r['modelo'],
                'serie'        => $r['serie'],
                'ip_address'   => $r['ip_address'],
                'firmware'     => $r['firmware'],
                'id_empleado'  => $idEmpleado,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        if ($insert) DB::table('impresoras')->insert($insert);
        $this->log[] = "✅ impresoras: {$this->count($insert)} registros";
    }

    private function importarInsumos(string $content): void
    {
        $filas = $this->parseCopy($content, 'insumos');
        if (!$filas) return;

        DB::table('insumos')->truncate();

        $insert = array_map(fn($r) => [
            'id_insumo'     => $this->int($r['id_insumo']),
            'nombre_insumo' => $r['nombre_insumo'],
            'numero_parte'  => $r['numero_parte'],
            'stock_minimo'  => $this->int($r['stock_minimo']) ?? 0,
            'stock_maximo'  => $this->int($r['stock_maximo']) ?? 0,
            'stock_actual'  => $this->int($r['stock_actual']) ?? 0,
            'created_at'    => now(),
            'updated_at'    => now(),
        ], $filas);

        DB::table('insumos')->insert($insert);
        $this->log[] = "✅ insumos: {$this->count($insert)} registros";
    }

    private function importarSuministros(string $content): void
    {
        $filas = $this->parseCopy($content, 'suministros');
        if (!$filas) return;

        // suministros depende de insumos y departamentos — ambos ya insertados
        $insert = [];
        foreach ($filas as $r) {
            $insert[] = [
                'id_suministro'      => $this->int($r['id_suministro']),
                'id_insumo'          => $this->int($r['id_insumo']),
                'id_departamento'    => $this->int($r['id_departamento']),
                'fecha_solicitud'    => $r['fecha_solicitud'],
                'cantidad_requerida' => $this->int($r['cantidad_requerida'] ?? null),
                'cantidad_entregada' => $this->int($r['cantidad_entregada'] ?? null),
                'estatus'            => $r['estatus'] ?? null,
                'notas'              => $r['notas'] ?? null,
                'created_at'         => now(),
                'updated_at'         => now(),
            ];
        }

        try {
            DB::table('suministros')->truncate();
            if ($insert) DB::table('suministros')->insert($insert);
            $this->log[] = "✅ suministros: {$this->count($insert)} registros";
        } catch (\Throwable $e) {
            $this->log[] = "⚠  suministros omitido (schema pendiente): " . $e->getMessage();
        }
    }

    private function importarRangosIPs(string $content): void
    {
        $filas = $this->parseCopy($content, 'cat_rangos_ips');
        if (!$filas) return;

        DB::table('cat_rangos_ips')->truncate();

        $insert = array_map(fn($r) => [
            'id_rango'       => $this->int($r['id_rango']),
            'area_nombre'    => $r['area_nombre'],
            'ip_inicial'     => $r['ip_inicial'],
            'ip_final'       => $r['ip_final'],
            'capacidad_total'=> $this->int($r['capacidad_total']),
            'created_at'     => now(),
            'updated_at'     => now(),
        ], $filas);

        DB::table('cat_rangos_ips')->insert($insert);
        $this->log[] = "✅ cat_rangos_ips: {$this->count($insert)} registros";
    }

    private function count(array $arr): int { return count($arr); }
}
