<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Livewire\Forms\EmailForm;
use App\Livewire\Forms\NombreForm;

class ValidarCorreos extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $fileExcel;
    public $empleadosIncompletos = [];

    public $originalCorreo;
    public $originalNombre;
    public $empleadoAEliminar;

    public $emailForm;
    public $nombreForm;

    public function render()
    {
        return view('livewire.admin.validar-correos', [
            'empleados' => \App\Models\Empleado::latest()->paginate(10),
        ]);
    }

    public function updatedFileExcel()
    {
        $this->validate([
            'fileExcel' => 'required|mimes:xlsx,xls,csv|max:10240', // Máximo 10MB
        ]);

        $ruta = $this->fileExcel->getRealPath();
        $datos = \Maatwebsite\Excel\Facades\Excel::toArray([], $ruta);
        $filas = $datos[0] ?? [];
        $encabezados = array_map('strtolower', array_shift($filas) ?? []);

        $celdaNombre = array_search('nombre', $encabezados);
        $celdaCorreo = array_search('correo', $encabezados);

        if ($celdaNombre === false || $celdaCorreo === false) {
            $this->dispatch('mostrar-error-excel');
            $this->reset('fileExcel');
            return;
        }

        $this->empleadosIncompletos = [];
        foreach ($filas as $fila) {
            $nombre = trim($fila[$celdaNombre] ?? '');
            $correo = trim($fila[$celdaCorreo] ?? '');
            
            if ($nombre !== '' && $correo === '') {
                $this->empleadosIncompletos[] = [
                    'dato' => $nombre,
                    'error' => 'Correo electrónico'
                ];
            }    
            elseif ($nombre === '' && $correo !== '') {
                $this->empleadosIncompletos[] = [
                    'dato' => $correo,
                    'error' => 'Nombre completo'
                ];
            }
        }

        if (count($this->empleadosIncompletos) > 0) {
            $this->dispatch('mostrar-nombres-sin-correo');
            $this->reset('fileExcel');
            return;
        }

        try {
            \Maatwebsite\Excel\Facades\Excel::import(new \App\Imports\ImportarEmpleados, $this->fileExcel);
            $this->reset('fileExcel');
            session()->flash('message', '¡Excel importado y datos actualizados con éxito!');
        } catch (\Exception $e) {
            session()->flash('error', 'Hubo un error al importar: ' . $e->getMessage());
        }
    }

    #[\Livewire\Attributes\On('limpiar-errores-registro')]
    public function prepararNuevoEmpleado()
    {
    $this->resetErrorBag();    
    $this->reset(['editarNombre', 'editarCorreo']);
        $this->reset(['crearNombre', 'crearCorreo']);
    }

    public function agregarEmpleado()
    {  
        $this->emailForm->validate();
        $this->nombreForm->validate();

        if (\App\Models\Empleado::where('correo', $this->crearCorreo)->exists()) {
            $this->addError('crearCorreo', 'Este correo ya está registrado');
            return;
        }

        \App\Models\Empleado::create([
            'nombre' => $this->crearNombre,
            'correo' => $this->crearCorreo,
        ]);

        $this->emailForm->reset();
        $this->nombreForm->reset();
        $this->dispatch('mostrar-toast', mensaje: 'Empleado(a) agregado(a) con éxito');

    }

    public function editarEmpleado($correo)
    {
        $this->resetErrorBag();
        $this->validate([
        'editarNombre' => $this->nombreForm->rules()['nombre'],
        'editarCorreo' => $this->emailForm->rules()['correo'],
    ], array_merge($this->nombreForm->messages(), $this->emailForm->messages()));

        $empleado = \App\Models\Empleado::where('correo', $correo)->first();
        $this->editarCorreo = $empleado->correo;
        $this->editarNombre = $empleado->nombre;
        $this->originalCorreo = $empleado->correo;
        $this->originalNombre = $empleado->nombre;
    
        $this->dispatch('abrir-modal-edicion');
    }

    public function actualizarEmpleado()
    {
        $this->resetValidation(['editarNombre', 'editarCorreo']);
        if ($this->editarNombre === $this->originalNombre && $this->editarCorreo === $this->originalCorreo) {
            $this->addError('editarNombre', 'No se detectaron modificaciones en el nombre');
            $this->addError('editarCorreo', 'No se detectaron modificaciones en el correo');
            return;
        }
        
        $reglas = $this->formularioBase->rules();
        $this->validate([
            'editarNombre' => $reglas['nombre'],
            'editarCorreo' => $reglas['correo'],
        ], $this->formularioBase->messages());

        if ($this->editarCorreo !== $this->originalCorreo) {
            if (\App\Models\Empleado::where('correo', $this->editarCorreo)->exists()) {
                $this->addError('editarCorreo', 'Este correo ya está registrado');
                return;
            }
        }

        $empleado = \App\Models\Empleado::where('correo', $this->originalCorreo)->first();
        if($empleado) {
            $empleado->update([
                'nombre' => $this->editarNombre,
                'correo' => $this->editarCorreo,
            ]);
        }
        $this->reset(['editarNombre', 'editarCorreo', 'originalNombre', 'originalCorreo']);
        $this->dispatch('mostrar-toast', mensaje: 'Empleado(a) actualizado(a) con éxito');
    }

    public function prepararEliminacion($correo, $nombre)
    {
        $this->empleadoAEliminar = ['correo' => $correo, 'nombre' => $nombre];
    }

    public function confirmarEliminacion()
    {
        if ($this->empleadoAEliminar) {
            $correo = $this->empleadoAEliminar['correo'];
            $empleado = \App\Models\Empleado::where('correo', $correo)->first();

        if ($empleado) {
            $empleado->delete();
            $this->dispatch('mostrar-toast', mensaje: 'Empleado(a) eliminado(a) con éxito');
        }
            $this->reset('empleadoAEliminar');
        }
    }
}
