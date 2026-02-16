<?php

namespace App\Livewire\Tickets;
use App\Livewire\Forms\TicketForm;
use App\Models\Type;
use App\Models\Area;

use Livewire\Component;

class TicketCreate extends Component
{
    public TicketForm $form;

    public function createTicket()
    {
        //Validamos y creamos el ticket en la base de datos
        $ticket = $this->form->createDBTicket();

        //Mandamos el mensaje de la alerta de exito
        session()->flash('createTicket', "¡Tu ticket se ha enviado exitosamente! - ID del ticket: {$ticket->id}");

        // Redireccion full reload
        return redirect()->to(route("bienvenida"));

        // Redireccion livewire
        // return $this->redirect('/', navigate: true);
    }

    public function render()
    {
        return view('livewire.tickets.ticket-create', [
            'tipos' => Type::all(),
            'areas' => Area::all(),
        ]);

    }
}
