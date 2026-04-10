<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Imports\ImportarEmpleados;
use App\Livewire\Forms\EmailForm;
use App\Livewire\Forms\NameForm;
use App\Livewire\Attributes\On;

class ValidarCorreos extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $fileExcel;
    public $errorMessage;
    public $empleadosIncompletos = [];
    public $empleadoId;
    public $formKey = 0;
    public EmailForm $eForm;
    public NameForm $nForm;

    public function render()
    {
        return view('livewire.admin.validar-correos', [
            'empleados' => \App\Models\Empleado::paginate(10),
        ]);
    }

    // Carga masiva de empleados mediante Excel
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

            // validación de columnas correctas
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

            // validación de datos incompletos
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

    // Preparar formulario para agregar nuevo empleado (visual)
    public function prepararAdicion()
    {
        $this->resetValidation();
        $this->nForm->reset();
        $this->eForm->reset();
        $this->formKey++;

        $this->js("document.getElementById('modalAgregarEmpleado').showModal();");    }

    // Agregar empleado manualmente
    public function agregarEmpleado()
    {
        $this->resetValidation(); 
        $this->validate();

        $correo = strtolower(trim($this->eForm->correo));
        $nombre = mb_strtoupper(trim($this->nForm->nombre), 'UTF-8');

        // se verifica que el correo no existe ya
        if (\App\Models\Empleado::where('correo', $correo)->exists()) {
            $this->eForm->addError('correo', 'Este correo ya está registrado en el sistema');
            return;
        }

        \App\Models\Empleado::create([
            'nombre' => $nombre,
            'correo' => $correo,
        ]);

        $this->nForm->reset();
        $this->eForm->reset();

        $this->js("document.getElementById('modalAgregarEmpleado').close();");
        session()->flash('message', 'Empleado agregado con éxito');
    }

    // Preparar edición y eliminación del empleado (visual)
    public function prepararEdicionEliminacion($id)
    {
        $this->resetValidation(); 
        
        $empleado = \App\Models\Empleado::find($id);
        if ($empleado) {
            $this->empleadoId = $empleado->id;            
            $this->nForm->nombre = $empleado->nombre;
            $this->eForm->correo = $empleado->correo;
            $this->formKey++;
        }
    }

    // Actualizar empleado (edición)
    public function actualizarEmpleado()
    {
        $this->validate();

        $nuevoNombre = mb_strtoupper(trim($this->nForm->nombre), 'UTF-8');
        $nuevoCorreo = strtolower(trim($this->eForm->correo));

        // Buscamos al empleado actual directamente de la BD
        $empleado = \App\Models\Empleado::find($this->empleadoId);

        if ($empleado) {
            // 1. REGLA: Validar si la info nueva es exactamente igual a la de la BD
            if ($empleado->nombre === $nuevoNombre && $empleado->correo === $nuevoCorreo) {
                $this->nForm->addError('nombre', 'No se detectaron modificaciones');
                $this->eForm->addError('correo', 'No se detectaron modificaciones');
                return;
            }

            // 2. REGLA: Validar que el nuevo correo no le pertenezca a OTRO empleado
            if ($empleado->correo !== $nuevoCorreo && \App\Models\Empleado::where('correo', $nuevoCorreo)->exists()) {
                $this->eForm->addError('correo', 'Este correo ya está registrado en otro empleado');
                return;
            }

            // 3. Si todo está bien, actualizamos
            $empleado->update([
                'nombre' => $nuevoNombre,
                'correo' => $nuevoCorreo,
            ]);
        }

        // Limpieza y cierre
        $this->nForm->reset();
        $this->eForm->reset();
        $this->js("document.getElementById('modalEditarEmpleado').close();");
        session()->flash('message', 'Empleado actualizado con éxito');
    }

    public function eliminarEmpleado()
    {
        $empleado = \App\Models\Empleado::find($this->empleadoId);
        if ($empleado) {
            $empleado->delete();
        }

        // Si la tabla se queda vacía, se reinica el contador del ID de empleados
        if (\App\Models\Empleado::count() === 0) {
            // Detectamos qué motor de base de datos estamos usando
            $driver = \Illuminate\Support\Facades\DB::getDriverName();
            
            if ($driver === 'sqlite') {
                // Comando específico para SQLite (Local)
                \Illuminate\Support\Facades\DB::statement("DELETE FROM sqlite_sequence WHERE name = 'empleados';");
            } else {
                // Comando específico para MySQL (Producción)
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE empleados AUTO_INCREMENT = 1;");
            }
        }

        $this->reset('empleadoId');

        $this->js("document.getElementById('modalEliminarEmpleado').close();");
        session()->flash('message', 'Empleado eliminado con éxito');
    }
}
