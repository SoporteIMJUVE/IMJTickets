<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DepartamentosSeeder extends Seeder
{
    public function run(): void
    {
        $file = base_path('../Base-de-Datos/directorio_imjuve.xlsx');
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getSheetByName('Directorio');
        $rows = $sheet->toArray(null, true, true, false);

        $areas = collect($rows)
            ->skip(1) // saltar encabezado
            ->pluck(0) // columna Área
            ->filter()
            ->map(fn($a) => trim($a))
            ->unique()
            ->values();

        foreach ($areas as $area) {
            DB::table('departamentos')->insertOrIgnore(['nombre' => $area]);
        }

        $this->command->info("Departamentos: {$areas->count()} insertados.");
    }
}
