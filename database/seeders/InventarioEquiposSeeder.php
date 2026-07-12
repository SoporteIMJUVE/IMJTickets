<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InventarioEquiposSeeder extends Seeder
{
    /**
     * Mapa de sheets del Excel → tipo canónico y fila de inicio de datos.
     * El nuevo inventario ABRIL 2026 usa:
     *   laptop          → fila índice 3 (0-based), columnas: No.|Inv|NombreEquipo|NombreUsuario|Perfil|Area|Marca|Modelo|Serial|Cargador|DockMarca|DockModelo|DockSerie|IPv4|MAC|Responsiva|Candado|Observaciones
     *   PC Especializadas → fila 3, columnas similares
     *   PC Avanzadas    → fila 3
     */
    private array $sheets = [
        'laptop'           => ['tipo' => 'Laptop',            'dataRow' => 3],
        'PC  Especializadas' => ['tipo' => 'PC Especializada', 'dataRow' => 3],
        'PC Avanzadas'     => ['tipo' => 'PC Avanzada',       'dataRow' => 3],
    ];

    public function run(): void
    {
        $file = base_path('../Base-de-Datos/NUEVO INVENTARIO IMJUVE  ABRIL 2026.1xlsx.xlsx');
        $spreadsheet = IOFactory::load($file);

        $total = 0;

        foreach ($this->sheets as $sheetName => $conf) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (!$sheet) {
                $this->command->warn("Hoja '{$sheetName}' no encontrada, omitiendo.");
                continue;
            }

            $rows = $sheet->toArray(null, true, true, false);
            $count = 0;

            foreach (array_slice($rows, $conf['dataRow']) as $row) {
                // Validar que tenga al menos nombre de usuario o equipo
                $nombreUsuario = trim($row[3] ?? '');
                $nombreEquipo  = trim($row[2] ?? '');
                if (!$nombreUsuario && !$nombreEquipo) continue;

                $area = trim($row[5] ?? '');

                // Laptop: No.|Inv|NombreEquipo|NombreUsuario|Perfil|Area|Marca|Modelo|Serial|Cargador|DockMarca|DockModelo|DockSerie|IPv4|MAC|Responsiva|Candado|Obs
                // PC: similar layout
                DB::table('inventario_equipos')->insert([
                    'tipo'          => $conf['tipo'],
                    'consecutivo'   => is_numeric($row[0]) ? (int)$row[0] : null,
                    'num_inventario'=> trim($row[1] ?? '') ?: null,
                    'nombre_equipo' => $nombreEquipo ?: null,
                    'nombre_usuario'=> $nombreUsuario ?: null,
                    'perfil'        => trim($row[4] ?? '') ?: null,
                    'area'          => $area ?: null,
                    'cpu_marca'     => trim($row[6] ?? '') ?: null,
                    'cpu_modelo'    => trim($row[7] ?? '') ?: null,
                    'cpu_serie'     => trim($row[8] ?? '') ?: null,
                    'cargador_serie'=> trim($row[9] ?? '') ?: null,
                    'docking_marca' => trim($row[10] ?? '') ?: null,
                    'docking_modelo'=> trim($row[11] ?? '') ?: null,
                    'docking_serie' => trim($row[12] ?? '') ?: null,
                    'ipv4'          => trim($row[13] ?? '') ?: null,
                    'mac'           => trim($row[14] ?? '') ?: null,
                    'responsiva'    => trim($row[15] ?? '') ?: null,
                    'candado'       => trim($row[16] ?? '') ?: null,
                    'observaciones' => trim($row[17] ?? '') ?: null,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
                $count++;
            }

            $this->command->info("  {$sheetName} ({$conf['tipo']}): {$count} equipos.");
            $total += $count;
        }

        $this->command->info("Inventario Equipos: {$total} registros en total.");
    }
}
