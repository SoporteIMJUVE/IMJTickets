<?php

namespace App\Imports;

use App\Models\Empleado;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Para usar la primera fila como encabezados
use Maatwebsite\Excel\Concerns\WithUpserts; // Para permitir actualizaciones basadas en una columna única (Update + Insert)

class EmpleadosImport implements ToModel, WithHeadingRow, WithUpserts
{
    public function model(array $row)
    {
        return new Empleado([
            'nombre'          => $row['nombre'],
            'correo'          => $row['correo'],
        ]);
    }

    public function uniqueBy()
    {
        return 'correo'; //Buscar empleado que tenga el mismo correo para actualizarlo, si no existe, lo inserta
    }
}


