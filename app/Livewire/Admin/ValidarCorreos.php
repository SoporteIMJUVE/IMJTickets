<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUpdoads;

class ValidarCorreos extends Component
{
    public $fileExcel;

    public function render()
    {
        return view('livewire.admin.validar-correos', [
            'empleados' => \App\Models\Empleado::all(),
        ]);
    }

    public function updatedFileExcel()
    {
    $this->validate([
        'fileExcel' => 'required|mimes:xlsx,xls,csv|max:10240', // Máximo 10MB
    ]);

    try {
        \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\EmpleadosImport, $this->fileExcel);

        // Limpiar la variable y enviar un mensaje de éxito
        $this->reset('fileExcel');
        session()->flash('message', '¡Excel importado y datos actualizados con éxito!');
    } catch (\Exception $e) {
        session()->flash('error', 'Hubo un error al importar: ' . $e->getMessage());
    }
}
}
