<?php

namespace App\Livewire\Forms;

use Illuminate\Validation\Rule;
use App\Models\Ticket;

class TicketForm extends EmailForm
{
    public $descripcion;
    public $tipo = "error";
    public $area = "error";
    public $nombre;

    public function rules()
    {
        return array_merge(parent::rules(), [
            'descripcion' => [
                'required', 
                'min:5',
                // Closure para personalizar las validaciones y sus mensajes
                function (string $attribute, mixed $value, \Closure $fail) {
                    // Contraseñas
                    if (preg_match('/(cambi|olvid|re?esta|reset|perdi|nueva|solicit|reconoc|se?p[ae]|acuerd|recuerd|nuev|crea).*(contra)/i', $value)) {
                        $fail('Los trámites de contraseñas deben solicitarse a través de correo electrónico');
                    }

                    // Acceso y Gestión de Cuentas
                    $regexAcceso = '/(no.*(pued|permite|deja)|sin.*acce|bloque).*(ingres|entr|acce).*(correo|cuenta)/i';
                    $regexGestion = '/(crea|alta|nuev|baja|elimina).*(correo|cuenta|usuari)/i';

                    if (preg_match($regexAcceso, $value) || preg_match($regexGestion, $value)) {
                        $fail('Los trámites de cuentas deben solicitarse mediante oficio');
                    }
                },
            ],
            'tipo'        => ['required', Rule::notIn(['error'])],
            'area'        => ['required', Rule::notIn(['error'])],
            'nombre'      => ['required', 'max:30', 'regex:/^\w+\s+\w+/'],
        ]);
    }

    public function messages()
    {
        return array_merge(parent::messages(), [
            'descripcion.required'  => 'Es necesario ingresar una descripción',
            'descripcion.min'       => 'La descripción debe tener al menos 5 caracteres',
            'tipo.not_in'           => 'Es necesario ingresar un tipo de incidente',
            'area.not_in'           => 'Es necesario ingresar un área',
            'nombre.required'       => 'Es necesario ingresar un nombre',
            'nombre.max'            => 'El nombre no puede exceder los 30 caracteres',
            'nombre.regex'          => 'Debes ingresar al menos un nombre y un apellido',
        ]);
    }

    public function createDBTicket()
    {
        $this->validate();

        $ticket = Ticket::create([
            'nombre'      => $this->nombre,
            'correo'      => $this->correo,
            'area'        => $this->area,
            'tipo'        => $this->tipo,
            'descripcion' => $this->descripcion,
            'status'      => 0,
        ]);

        return $ticket;
    }
}
