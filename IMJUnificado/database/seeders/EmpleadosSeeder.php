<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class EmpleadosSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('../Base-de-Datos/directorio_imjuve.xlsx');
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getSheetByName('Directorio');
        $rows = $sheet->toArray(null, true, true, false);

        // Precarga mapa area → id
        $deptos = DB::table('departamentos')->pluck('id_departamento', 'nombre');

        $inserted = 0;
        foreach (array_slice($rows, 1) as $row) { // skip header
            [$area, $extension, $nombre, $apPaterno, $apMaterno] = array_pad($row, 5, null);

            if (empty($nombre)) continue;

            $nombre     = trim($nombre ?? '');
            $apPaterno  = trim($apPaterno ?? '');
            $apMaterno  = trim($apMaterno ?? '');
            $area       = trim($area ?? '');
            $extension  = is_numeric($extension) ? (int) $extension : null;

            $idDepto = $deptos[$area] ?? null;

            // Construir correo institucional heurístico
            $correo = null;
            if ($nombre && $apPaterno) {
                $n = iconv('UTF-8', 'ASCII//TRANSLIT', strtolower($nombre));
                $a = iconv('UTF-8', 'ASCII//TRANSLIT', strtolower($apPaterno));
                $nParts = explode(' ', preg_replace('/\s+/', ' ', trim($n)));
                $correo = $nParts[0] . '.' . preg_replace('/\s+/', '', $a) . '@imjuve.gob.mx';
            }

            DB::table('empleados')->insert([
                'nombre'          => $nombre,
                'apellido_paterno'=> $apPaterno ?: null,
                'apellido_materno'=> $apMaterno ?: null,
                'puesto'          => null,
                'correo'          => $correo,
                'id_departamento' => $idDepto,
                'activo'          => true,
                'fecha_alta'      => now(),
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // Insertar extensión telefónica
            $empId = DB::getPdo()->lastInsertId();
            if ($extension) {
                DB::table('telefonos')->insert([
                    'extension'   => $extension,
                    'id_empleado' => $empId,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
            }

            $inserted++;
        }

        $this->command->info("Empleados: {$inserted} insertados.");
    }
}
