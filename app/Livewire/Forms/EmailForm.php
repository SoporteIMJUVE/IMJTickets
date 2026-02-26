<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use App\Models\Ticket;
use App\Models\Empleado;

class EmailForm extends Form
{
    public $correo;
    public $dominio = "@imjuventud.gob.mx";
    public $validarEmpleado = false;

    public function rules()
    {
        $reglas = [
            'correo' => ['required', 'email', 'ends_with:'.$this->dominio],
        ];
        
        if ($this->validarEmpleado) {
            $reglas['correo'][] = 'exists:empleados,correo';
        }

        return $reglas;
    }

    public function messages()
    {
        return [
            'correo.required' => 'Es necesario ingresar un correo',
            'correo.email' => 'El correo ingresado no es válido',
            'correo.ends_with' => 'El correo debe ser institucional ('.$this->dominio.')',
            'correo.exists' => 'El correo no está registrado en el sistema',
        ];
    }

    public function userValidation()
    {
        $this->validate();

        // Validación adicional si tiene tickets
        if (!Ticket::where('correo', $this->correo)->exists()) {
            $this->addError('correo', 'Este correo no tiene ningun ticket asociado');
            return false;
        }
        
        return true;
    }
}