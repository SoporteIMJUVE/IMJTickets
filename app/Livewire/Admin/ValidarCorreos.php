<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ValidarCorreos extends Component
{
    use WithFileUploads;
    use WithPagination;

    public $fileExcel;
    public $crearNombre;
    public $crearCorreo;
    public $editarNombre;
    public $editarCorreo;
    public $originalCorreo;
    public $originalNombre;
    public $empleadoAEliminar;
    

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
        $titulo = array_map('strtolower', $datos[0][0] ?? []);

        if (!in_array('nombre', $titulo) || !in_array('correo', $titulo)) {
            $this->dispatch('mostrar-error-excel');
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
        $this->validate([
            'crearNombre' => ['required', 'max:50', 'regex:/^\S+\s+\S+\s+\S+/'],
            'crearCorreo' => ['required', 'email', 'ends_with:@imjuventud.gob.mx'],
        ], [
            'crearNombre.required'  => 'Es necesario ingresar un nombre',
            'crearNombre.regex'     => 'Debes ingresar el nombre completo del empleado',
            'crearCorreo.required'  => 'Es necesario ingresar un correo',
            'crearCorreo.email'     => 'El correo ingresado no es válido',
            'correo.ends_with'      => 'El correo debe ser institucional (@imjuventud.gob.mx)',
        ]);

        if (\App\Models\Empleado::where('correo', $this->correo)->exists()) {
            $this->addError('correo', 'Este correo ya está registrado');
            return;
        }

        \App\Models\Empleado::create([
            'nombre' => $this->crearNombre,
            'correo' => $this->crearCorreo,
        ]);

        $this->reset(['crearNombre', 'crearCorreo']);
        $this->dispatch('mostrar-toast', mensaje: 'Empleado agregado con éxito');

    }

    public function editarEmpleado($correo)
    {
        $this->resetErrorBag();
        $empleado = \App\Models\Empleado::where('correo', $correo)->first();
        if (!$empleado) {
            dd("No se encontró al empleado con el correo: " . $correo); 
        }

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
        
        $this->validate([
            'editarNombre' => ['required', 'max:50', 'regex:/^\S+\s+\S+\s+\S+/'],
            'editarCorreo' => ['required', 'email', 'ends_with:@imjuventud.gob.mx'],
        ], [
            'editarNombre.required' => 'Es necesario ingresar un nombre',
            'editarNombre.regex'    => 'Debes ingresar el nombre completo del empleado',
            'editarCorreo.required'  => 'Es necesario ingresar un correo',
            'editarCorreo.email'     => 'El correo ingresado no es válido',
            'editarCorreo.ends_with' => 'El correo debe ser institucional (@imjuventud.gob.mx)',
        ]);

        if ($this->editarCorreo !== $this->originalCorreo) {
            if (\App\Models\Empleado::where('correo', $this->editarCorreo)->exists()) {
                $this->addError('editarCorreo', 'Este correo ya pertenece a otro empleado.');
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
        $this->dispatch('mostrar-toast', mensaje: 'Empleado actualizado con éxito');
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
            $this->dispatch('mostrar-toast', mensaje: 'Empleado eliminado con éxito');
        }
            $this->reset('empleadoAEliminar');
        }
    }
}
