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
            'descripcion' => ['required', 'min:5', 'max:100'],
            'tipo'        => ['required', Rule::notIn(['error'])],
            'area'        => ['required', Rule::notIn(['error'])],
            'nombre'      => ['required', 'max:30', 'regex:/^\w+\s+\w+/'],
        ]);
    }

    public function messages()
    {
        return array_merge(parent::messages(), [
            'descripcion.required' => 'Es necesario ingresar una descripción',
            'descripcion.min'      => 'La descripción debe tener al menos 5 caracteres',
            'descripcion.max'      => 'La descripción no puede exceder los 100 caracteres',
            'tipo.not_in'          => 'Es necesario ingresar un tipo de incidente',
            'area.not_in'          => 'Es necesario ingresar un área',
            'nombre.required'      => 'Es necesario ingresar un nombre',
            'nombre.max'           => 'El nombre no puede exceder los 30 caracteres',
            'nombre.regex'         => 'Debes ingresar al menos un nombre y un apellido',
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
