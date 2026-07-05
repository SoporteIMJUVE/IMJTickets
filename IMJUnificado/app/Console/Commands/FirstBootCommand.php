<?php

namespace App\Console\Commands;

use App\Services\PostgresDumpImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FirstBootCommand extends Command
{
    protected $signature   = 'app:boot {--force : Forzar reimportación aunque no sea el primer arranque}';
    protected $description = 'Detecta primer arranque e importa datos desde los sistemas legados';

    // Rutas de los respaldos de los sistemas legados
    private const POSTGRES_DUMP = '/home/robute/Documentos/codes/IPMJ_proyect/DB_source/sistemitas.sql';
    private const MYSQL_DUMP    = '/home/robute/Documentos/codes/IPMJ_proyect/DB_source/imjtickets.sql';

    // Archivo donde se persiste el estado de arranque (sobrevive reinicios)
    private const STATE_FILE = 'boot_state.json';

    public function handle(): int
    {
        $state   = $this->loadState();
        $esFirst = $state['boot_count'] === 0;
        $forzado = $this->option('force');

        $this->line('');
        $this->line('  <fg=yellow>IMJUnificado — Sistema de arranque</fg=yellow>');
        $this->line('  Arranques previos: <fg=cyan>' . $state['boot_count'] . '</fg=cyan>');
        $this->line('');

        if (!$esFirst && !$forzado) {
            $this->line('  <fg=green>✓</fg=green> Arranque normal — sin importación.');
            $this->incrementarContador($state);
            return self::SUCCESS;
        }

        if ($forzado && !$esFirst) {
            $this->warn('  Modo --force activado. Se reimportarán los datos legados.');
        } else {
            $this->info('  Primer arranque detectado. Iniciando importación de datos legados...');
        }

        $this->line('');

        // ── 1. Importar desde PostgreSQL (sistemitas) ─────────────────────────
        $this->importarPostgres();

        // ── 2. Importar desde MySQL (IMJTickets) ──────────────────────────────
        $this->importarMysql();

        // ── 3. Recalcular contadores en cat_rangos_ips ────────────────────────
        $this->recalcularRangos();

        // ── 4. Guardar estado ─────────────────────────────────────────────────
        $state['ultima_importacion'] = now()->toIso8601String();
        $state['fuentes']            = [
            'postgres' => file_exists(self::POSTGRES_DUMP),
            'mysql'    => file_exists(self::MYSQL_DUMP),
        ];

        $this->incrementarContador($state);

        $this->line('');
        $this->info('  Importación completada. El sistema está listo.');
        $this->line('');

        return self::SUCCESS;
    }

    // ─── Importador PostgreSQL ────────────────────────────────────────────────

    private function importarPostgres(): void
    {
        $this->line('  <fg=blue>▶ Fuente 1: Sistema Inventario (PostgreSQL)</fg=blue>');

        if (!file_exists(self::POSTGRES_DUMP)) {
            $this->warn('    Archivo no encontrado: ' . self::POSTGRES_DUMP);
            $this->warn('    Coloca el dump de sistemitas en DB_source/sistemitas.sql');
            return;
        }

        try {
            $importer = new PostgresDumpImporter(self::POSTGRES_DUMP);
            $log      = $importer->run();

            foreach ($log as $linea) {
                $this->line("    {$linea}");
            }
        } catch (\Throwable $e) {
            $this->error('    Error durante la importación PostgreSQL:');
            $this->error('    ' . $e->getMessage());
        }
    }

    // ─── Importador MySQL (IMJTickets) ────────────────────────────────────────

    private function importarMysql(): void
    {
        $this->line('');
        $this->line('  <fg=blue>▶ Fuente 2: IMJTickets (MySQL)</fg=blue>');

        if (!file_exists(self::MYSQL_DUMP)) {
            $this->warn('    Archivo no encontrado: ' . self::MYSQL_DUMP);
            $this->warn('    Cuando tengas el dump, colócalo en DB_source/imjtickets.sql');
            $this->line('    (se importará al ejecutar php artisan app:boot --force)');
            return;
        }

        try {
            $this->importarTicketsDesdeMySQL(self::MYSQL_DUMP);
        } catch (\Throwable $e) {
            $this->error('    Error durante la importación MySQL:');
            $this->error('    ' . $e->getMessage());
        }
    }

    private function importarTicketsDesdeMySQL(string $path): void
    {
        $content = file_get_contents($path);

        // MySQL usa INSERT INTO — extraemos por tabla
        $this->importarTablaMySQL($content, 'areas',    ['id', 'nombre'],          'areas',   ['id', 'nombre']);
        $this->importarTablaMySQL($content, 'tipos',    ['id', 'nombre'],          'tipos',   ['id', 'nombre']);
        $this->importarTablaMySQL($content, 'tickets',  null,                      'tickets', null, skipIfExists: true);

        // empleados de IMJTickets → merge con empleados existentes por correo
        $this->fusionarEmpleadosMySQL($content);
    }

    /**
     * Importa una tabla desde un dump MySQL (formato INSERT INTO).
     * Si $cols es null, toma todas las columnas del INSERT.
     */
    private function importarTablaMySQL(
        string $content,
        string $tablaOrigen,
        ?array $colsOrigen,
        string $tablaDestino,
        ?array $colsDestino,
        bool $skipIfExists = false
    ): void {
        if ($skipIfExists && DB::table($tablaDestino)->exists()) {
            $this->line("    ⏭  {$tablaDestino}: ya tiene datos, se omite");
            return;
        }

        // Buscar INSERT INTO `tabla` VALUES (...)
        $pattern = '/INSERT INTO `' . preg_quote($tablaOrigen, '/') . '`\s*(?:\([^)]+\))?\s*VALUES\s*(.*?);/si';
        if (!preg_match_all($pattern, $content, $matches)) {
            $this->warn("    ⚠  No se encontraron INSERTs para '{$tablaOrigen}'");
            return;
        }

        // Extraer columnas del primer INSERT
        $colPattern = '/INSERT INTO `' . preg_quote($tablaOrigen, '/') . '`\s*\(([^)]+)\)/i';
        if ($colsOrigen === null && preg_match($colPattern, $content, $cm)) {
            $colsOrigen  = array_map(fn($c) => trim($c, ' `'), explode(',', $cm[1]));
            $colsDestino = $colsOrigen;
        }

        $count = 0;
        foreach ($matches[1] as $valueBlock) {
            // Cada fila: (val1, val2, ...)
            preg_match_all('/\(([^)]+)\)/', $valueBlock, $rows);
            foreach ($rows[1] as $rowStr) {
                $vals = str_getcsv($rowStr, ',', "'");
                $row  = [];
                foreach (($colsOrigen ?? []) as $i => $col) {
                    $dest      = ($colsDestino ?? $colsOrigen)[$i] ?? $col;
                    $v         = $vals[$i] ?? null;
                    $row[$dest] = ($v === 'NULL' || $v === null) ? null : $v;
                }
                $row['created_at'] = now();
                $row['updated_at'] = now();

                try {
                    DB::table($tablaDestino)->insertOrIgnore($row);
                    $count++;
                } catch (\Throwable) {}
            }
        }

        $this->line("    ✅ {$tablaDestino}: {$count} registros");
    }

    /**
     * Fusiona la tabla empleados de IMJTickets con los empleados ya importados.
     * Busca por correo: si existe, añade el correo; si no, lo inserta.
     */
    private function fusionarEmpleadosMySQL(string $content): void
    {
        $pattern = '/INSERT INTO `empleados`\s*\(([^)]+)\)\s*VALUES\s*(.*?);/si';
        if (!preg_match_all($pattern, $content, $m)) {
            $this->warn('    ⚠  No se encontraron empleados en el dump MySQL');
            return;
        }

        $cols       = array_map(fn($c) => trim($c, ' `'), explode(',', $m[1][0]));
        $insertados = 0;
        $vinculados = 0;

        foreach ($m[2] as $block) {
            preg_match_all('/\(([^)]+)\)/', $block, $rows);
            foreach ($rows[1] as $rowStr) {
                $vals  = str_getcsv($rowStr, ',', "'");
                $datos = [];
                foreach ($cols as $i => $col) {
                    $v         = $vals[$i] ?? null;
                    $datos[$col] = ($v === 'NULL' || $v === null) ? null : $v;
                }

                $correo = $datos['correo'] ?? $datos['email'] ?? null;
                if (!$correo) continue;

                $existente = DB::table('empleados')->where('correo', $correo)->first();

                if ($existente) {
                    // Solo marca el correo como vinculado (ya tiene nombre completo)
                    $vinculados++;
                } else {
                    // Insertar como empleado mínimo (solo correo, sin nombre completo)
                    DB::table('empleados')->insertOrIgnore([
                        'nombre'    => $datos['nombre'] ?? explode('@', $correo)[0],
                        'correo'    => $correo,
                        'activo'    => 1,
                        'fecha_alta'=> now(),
                        'created_at'=> now(),
                        'updated_at'=> now(),
                    ]);
                    $insertados++;
                }
            }
        }

        $this->line("    ✅ empleados (desde IMJTickets): {$insertados} nuevos, {$vinculados} ya existían");
    }

    // ─── Recalcular rangos IP ─────────────────────────────────────────────────

    private function recalcularRangos(): void
    {
        if (!Schema::hasTable('cat_rangos_ips') || !Schema::hasTable('inventario_ips_completo')) {
            return;
        }

        $this->line('');
        $this->line('  <fg=blue>▶ Recalculando disponibilidad IP</fg=blue>');

        // Este cálculo lo hace el módulo Network — aquí solo disparamos si tiene datos
        $rangos = DB::table('cat_rangos_ips')->count();
        $ips    = DB::table('inventario_ips_completo')->count();
        $this->line("    cat_rangos_ips: {$rangos} rangos, inventario_ips: {$ips} IPs registradas");
    }

    // ─── Estado de arranque ───────────────────────────────────────────────────

    private function stateFilePath(): string
    {
        return storage_path('app/' . self::STATE_FILE);
    }

    private function loadState(): array
    {
        $path = $this->stateFilePath();

        if (!file_exists($path)) {
            return ['boot_count' => 0, 'ultima_importacion' => null, 'fuentes' => []];
        }

        $data = json_decode(file_get_contents($path), true);
        return is_array($data) ? $data : ['boot_count' => 0, 'ultima_importacion' => null, 'fuentes' => []];
    }

    private function incrementarContador(array $state): void
    {
        $state['boot_count']    = ($state['boot_count'] ?? 0) + 1;
        $state['ultimo_arranque'] = now()->toIso8601String();

        file_put_contents($this->stateFilePath(), json_encode($state, JSON_PRETTY_PRINT));
    }
}
