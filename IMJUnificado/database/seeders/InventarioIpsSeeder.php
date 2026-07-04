<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class InventarioIpsSeeder extends Seeder
{
    /** Hojas de IP por área y su sigla/nombre */
    private array $areaSheets = [
        'DG'   => 'DIRECCIÓN GENERAL',
        'DBEJ' => 'DIRECCIÓN DE BIENESTAR Y ESTIMULOS A LA JUVENTUD',
        'DIEJ' => 'DIRECCIÓN DE INVESTIGACIÓN Y ESTUDIOS SOBRE JUVENTUDES',
        'DCSR' => 'DIRECCIÓN DE COORDINACIÓN SECTORIAL Y REGIONAL',
        'DEC'  => 'DIRECCIÓN DE EVALUACIÓN Y CONTROL',
        'DF'   => 'DIRECCIÓN DE FINANZAS',
        'DRHM' => 'DIRECCIÓN DE RECURSOS HUMANOS Y MATERIALES',
        'DAJ'  => 'DIRECCIÓN DE ASUNTOS JURÍDICOS',
        'DCS'  => 'DIRECCIÓN DE COMUNICACIÓN SOCIAL',
        'OIC'  => 'ÓRGANO INTERNO DE CONTROL',
        'SS'   => 'SUBDIRECCIÓN DE SISTEMAS',
    ];

    public function run(): void
    {
        $file = base_path('../Base-de-Datos/Inventario IPS.xlsx');
        $spreadsheet = IOFactory::load($file);

        // --- RANGOS (hoja RANGO, filas 3-13) ---
        $rangoSheet = $spreadsheet->getSheetByName('RANGO');
        $rangoRows  = $rangoSheet->toArray(null, true, true, false);
        $rangosInserted = 0;

        $siglasByName = array_flip($this->areaSheets);

        foreach (array_slice($rangoRows, 3) as $row) {
            $nombre = trim($row[4] ?? '');
            if (!$nombre || strtolower($nombre) === 'totales') continue;

            $ipInicial = trim($row[5] ?? '') ?: null;
            $ipFinal   = trim($row[6] ?? '') ?: null;
            $libres    = is_numeric($row[7] ?? null) ? (int)$row[7] : 0;
            $ocupadas  = is_numeric($row[8] ?? null) ? (int)$row[8] : 0;
            $total     = is_numeric($row[10] ?? null) ? (int)$row[10] : null;

            $sigla = $siglasByName[mb_strtoupper($nombre)] ?? null;

            DB::table('cat_rangos_ips')->insert([
                'area_nombre'    => $nombre,
                'siglas'         => $sigla,
                'ip_inicial'     => $ipInicial,
                'ip_final'       => $ipFinal,
                'capacidad_total'=> $total,
                'ocupadas'       => $ocupadas,
                'libres'         => $libres,
                'created_at'     => now(),
                'updated_at'     => now(),
            ]);
            $rangosInserted++;
        }
        $this->command->info("Rangos IPs: {$rangosInserted} insertados.");

        // --- INVENTARIO IPs (todas las hojas de área) ---
        $totalIps = 0;
        foreach (array_keys($this->areaSheets) as $sheetName) {
            $sheet = $spreadsheet->getSheetByName($sheetName);
            if (!$sheet) continue;

            $rows  = $sheet->toArray(null, true, true, false);
            $count = 0;

            foreach (array_slice($rows, 2) as $row) { // skip título + encabezado
                $ip = trim($row[1] ?? '');
                if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP)) continue;

                try {
                    DB::table('inventario_ips_completo')->insertOrIgnore([
                        'ip'                      => $ip,
                        'usuario'                 => trim($row[2] ?? '') ?: null,
                        'tipo_equipo'             => trim($row[3] ?? '') ?: null,
                        'institucional_o_personal'=> trim($row[4] ?? '') ?: null,
                        'marca'                   => trim($row[5] ?? '') ?: null,
                        'modelo'                  => trim($row[6] ?? '') ?: null,
                        'serie'                   => trim($row[7] ?? '') ?: null,
                        'mac'                     => trim($row[8] ?? '') ?: null,
                        'tipo_conexion'           => trim($row[9] ?? '') ?: null,
                        'config_red'              => trim($row[10] ?? '') ?: null,
                        'area_excel'              => trim($row[11] ?? '') ?: null,
                        'departamento_pestana'    => $sheetName,
                        'restricciones'           => trim($row[12] ?? '') ?: null,
                        'youtube'                 => trim($row[13] ?? '') ?: null,
                        'vimeo'                   => trim($row[14] ?? '') ?: null,
                        'spotify'                 => trim($row[15] ?? '') ?: null,
                        'otros_streaming'         => trim($row[16] ?? '') ?: null,
                        'facebook'                => trim($row[17] ?? '') ?: null,
                        'tiktok'                  => trim($row[18] ?? '') ?: null,
                        'instagram'               => trim($row[19] ?? '') ?: null,
                        'whatsapp_web'            => trim($row[20] ?? '') ?: null,
                        'otra_red_social'         => trim($row[21] ?? '') ?: null,
                        'sitios_gub'              => trim($row[22] ?? '') ?: null,
                        'noticias'                => trim($row[23] ?? '') ?: null,
                        'otro_permiso'            => trim($row[24] ?? '') ?: null,
                        'estatus'                 => trim($row[25] ?? 'Libre') ?: 'Libre',
                        'observaciones'           => trim($row[26] ?? '') ?: null,
                        'created_at'              => now(),
                        'updated_at'              => now(),
                    ]);
                    $count++;
                } catch (\Throwable $e) {
                    // IP duplicada u otro error, continuar
                }
            }

            $this->command->info("  {$sheetName}: {$count} IPs.");
            $totalIps += $count;
        }

        $this->command->info("IPs totales: {$totalIps} registros.");
    }
}
