<?php

namespace App\Imports;

use App\Models\Empleado;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Para usar la primera fila como encabezados
use Maatwebsite\Excel\Concerns\WithUpserts; // Para permitir actualizaciones basadas en una columna única (Update + Insert)
use Maatwebsite\Excel\Concerns\SkipsEmptyRows; // Para omitir filas vacías

class ImportarEmpleados implements ToModel, WithHeadingRow, WithUpserts, SkipsEmptyRows
{
    public function model(array $row)
    {
        $nombre = $row['nombre'] ?? '';
        $correo = $row['correo'] ?? '';

        if (empty(trim((string) $correo))) {
            return null; // Revisa que no haya filas vacías
        }

        return new Empleado([
            'nombre' => mb_strtoupper(trim((string) $nombre), 'UTF-8'), // Convertir a mayúsculas
            'correo' => strtolower(trim((string) $correo)),
        ]);
    }

    public function uniqueBy()
    {
        return 'correo'; //Buscar empleado que tenga el mismo correo para actualizarlo, si no existe, lo inserta
    }
}


