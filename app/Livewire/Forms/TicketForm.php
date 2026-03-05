<?php

namespace App\Livewire\Forms;

use Illuminate\Validation\Rule;
use App\Models\Ticket;

class TicketForm extends EmailForm
{
    public $descripcion;
    public $tipo = "error";
    public $area = "error";

    public function rules()
    {
        return array_merge(parent::rules(), [
            'descripcion' => ['required', 'min:5', 'max:100'],
            'tipo'        => ['required', Rule::notIn(['error'])],
            'area'        => ['required', Rule::notIn(['error'])],
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
        ]);
    }

    public function createDBTicket($nombre)
    {
        $this->validate();
        $ticket = Ticket::create([
            'nombre'      => $nombre,
            'correo'      => $this->correo,
            'area'        => $this->area,
            'tipo'        => $this->tipo,
            'descripcion' => $this->descripcion,
            'status'      => 0,
        ]);

        return $ticket;
    }
}
