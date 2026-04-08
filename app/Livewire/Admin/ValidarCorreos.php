<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Imports\ImportarEmpleados;

class ValidarCorreos extends Component
{
    use WithFileUploads;
    use WithPagination;
    public $fileExcel;
    public $errorMessage;
    public $empleadosIncompletos = [];
    
    public function render()
    {
        return view('livewire.admin.validar-correos', [
            'empleados' => \App\Models\Empleado::paginate(10),
        ]);
    }

    public function updatedFileExcel()
    {
        $this->validate([
            'fileExcel' => 'required|mimes:xlsx,xls,csv|max:10240', // Máximo 10MB
        ]);

        try {
            $datos = \Maatwebsite\Excel\Facades\Excel::toArray(new class implements \Maatwebsite\Excel\Concerns\ToArray {
                public function array(array $array) {}
            }, $this->fileExcel);

            $primeraHoja = $datos[0];
            $encabezados = $primeraHoja[0]; //primera fila

            $nombre = strtolower(trim($encabezados[0] ?? ''));
            $correo = strtolower(trim($encabezados[1] ?? ''));

            if($nombre !== 'nombre' && $correo !== 'correo') {
                $this->errorMessage = 'Ambas columnas están mal escritas (Nombre y Correo) ';
                $this->reset('fileExcel');
                $this->js("setTimeout(() => { document.getElementById('modalErrorFormato').showModal(); }, 150);");
                return;
            }
            elseif($nombre !== 'nombre') {
                $this->errorMessage = 'La primera columna está mal escrita (Nombre)';
                $this->reset('fileExcel');
                $this->js("setTimeout(() => { document.getElementById('modalErrorFormato').showModal(); }, 150);");
                return;
            }
            elseif($correo !== 'correo') {
                $this->errorMessage = 'La segunda columna está mal escrita (Correo)';
                $this->reset('fileExcel');
                $this->js("setTimeout(() => { document.getElementById('modalErrorFormato').showModal(); }, 150);");
                return;
            }

            $this->empleadosIncompletos = [];
            foreach (array_slice($primeraHoja, 1) as $index => $fila) {
                $nombreEmpleado = trim($fila[0] ?? '');
                $correoEmpleado = trim($fila[1] ?? '');

                if(empty($nombreEmpleado) && empty($correoEmpleado)) {
                    continue; // Si ambos están vacíos, ignoramos la fila completamente
                }
                if(!empty($nombreEmpleado) && empty($correoEmpleado)) {
                    $this->empleadosIncompletos[] = [
                        'dato' => $nombreEmpleado,
                        'error' => 'Correo institucional',
                    ];
                }
                if(empty($nombreEmpleado) && !empty($correoEmpleado)) {
                    $this->empleadosIncompletos[] = [
                        'dato' => $correoEmpleado,
                        'error' => 'Nombre del empleado',
                    ];
                }
            }

            // Si hay datos incompletos se muestra el modal
            if(count($this->empleadosIncompletos) > 0) {
                $this->reset('fileExcel');
                $this->js("setTimeout(() => { document.getElementById('modalDatosIncompletos').showModal(); }, 150);");
                return;
            }

            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\ImportarEmpleados, $this->fileExcel);

            $this->reset('fileExcel');
            session()->flash('message', 'Excel importado y datos actualizados con éxito');

        } catch (\Throwable $e) {
            $this->addError('fileExcel', 'Error: ' . $e->getMessage() . ' (Línea ' . $e->getLine() . ')');
        }
    }
}
