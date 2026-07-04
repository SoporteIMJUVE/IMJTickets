<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImpressorasInsumoSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('../Base-de-Datos/SOLICITUDES TONER Y STOCK.xlsx');
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getSheetByName('Hoja2');
        $rows = $sheet->toArray(null, true, true, false);

        // --- IMPRESORAS (filas 4 a 14, 0-based índice 3-13) ---
        $impresoras = 0;
        for ($i = 3; $i <= 13; $i++) {
            $row = $rows[$i] ?? [];
            $no = $row[0] ?? null;
            if (!is_numeric($no)) continue;

            $area  = trim($row[1] ?? '');
            $marca = trim($row[7] ?? '');
            $modelo = trim($row[8] ?? '');
            $firmware = trim($row[9] ?? '');
            $serie = trim($row[10] ?? '');
            $ip   = trim($row[11] ?? '');

            DB::table('impresoras')->insert([
                'area'       => $area ?: null,
                'marca'      => $marca ?: null,
                'modelo'     => $modelo ?: null,
                'firmware'   => $firmware ?: null,
                'serie'      => $serie ?: null,
                'ip_address' => $ip ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $impresoras++;
        }
        $this->command->info("Impresoras: {$impresoras} insertadas.");

        // --- INSUMOS (filas 20 a 28, 0-based índice 19-27) ---
        $insumos = 0;
        for ($i = 19; $i <= 27; $i++) {
            $row = $rows[$i] ?? [];
            $no  = $row[0] ?? null;
            if (!is_numeric($no)) continue;

            $nombre = trim($row[1] ?? '');
            if (!$nombre) continue;

            $numeroParte   = trim($row[6] ?? '') ?: null;
            $stockMinimo   = is_numeric($row[17] ?? null) ? (int)$row[17] : 0;
            $stockMaximo   = is_numeric($row[18] ?? null) ? (int)$row[18] : 0;
            $stockActual   = is_numeric($row[20] ?? null) ? (int)$row[20] : 0;

            DB::table('insumos')->insert([
                'nombre_insumo' => $nombre,
                'numero_parte'  => $numeroParte,
                'stock_minimo'  => $stockMinimo,
                'stock_maximo'  => $stockMaximo,
                'stock_actual'  => $stockActual,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
            $insumos++;
        }
        $this->command->info("Insumos: {$insumos} insertados.");
    }
}
