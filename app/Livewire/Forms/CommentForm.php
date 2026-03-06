<?php

namespace App\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;
use App\Models\Ticket;

class CommentForm extends Form
{
    public $comentarios;

    public function edit($id)
    {
        $ticket = Ticket::find($id);

        if ($ticket) {
            $ticket->update(['comentarios' => $this->comentarios]);
        }
        else {
            session()->flash('error', "No se encontró el ticket con ID: {$id}");
        }

        return $ticket;
    }
}
