<?php

namespace App\Livewire\Forms;

use Livewire\Form;
use App\Models\Ticket;

class EmailForm extends Form
{
    public $correo;

    public $dominio = "@imjuventud.gob.mx";

    public function rules()
    {
        return [
            'correo' => ['required', 'email', 'ends_with:'.$this->dominio],
        ];
    }

    public function messages()
    {
        return [
            'correo.required' => 'Es necesario ingresar un correo',
            'correo.email' => 'El correo ingresado no es válido',
            'correo.ends_with' => 'El correo debe ser institucional ('.$this->dominio.')',
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