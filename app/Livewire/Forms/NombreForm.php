<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class NombreForm extends Form
{
    public $nombre;

    public function rules()
    {
        return [
            'nombre' => ['required', 'max:50', 'regex:/^\S+\s+\S+\s+\S+/'],
        ];
    }

    public function messages()
    {
        return [
            'nombre.required' => 'Es necesario ingresar un nombre',
            'nombre.max'      => 'El nombre no puede exceder los 50 caracteres',
            'nombre.regex'    => 'Debes ingresar al menos un nombre y un apellido',
        ];
    }
}
