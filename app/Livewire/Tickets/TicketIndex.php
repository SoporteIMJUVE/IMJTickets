<?php

namespace App\Livewire\Tickets;

use Illuminate\Support\Facades\Auth;
use Livewire\WithPagination;
use Livewire\Component;
use App\Models\Ticket;
use App\Models\Area;
use App\Models\Type;

class TicketIndex extends Component
{
    use WithPagination;

    public $user;
    public $numEstados;
    public array $activeFilters = [];

    public function ticketProgress($id)
    {
        $ticket = Ticket::find($id);
        if ($ticket && $ticket->estado < 3) { // asumimos que 3 = Cerrado
            $ticket->increment('estado'); // incrementa 1 automáticamente en la BD

            // Si el estado cambia a "Atendiendo"
            if ($ticket->estado == 1) {
                $ticket->atendido_at = now();
                $ticket->save();
            }

            $ticket->refresh(); // refresca los datos del modelo
        }

        // Actualizamos la colección para que la tabla se refresque
        $this->tickets = Ticket::all();
    }

    public function setFilter($field, $filter)
    {
        $field = (string) $field;
        $filter = (string) $filter;

        // Aseguramos que existe el subarray del campo antes de leer/escribir
        if (! isset($this->activeFilters[$field]) || ! is_array($this->activeFilters[$field])) {
            $this->activeFilters[$field] = [];
        }

        // Si el filtro ya está activo, limpiamos el filtro
        if (isset($this->activeFilters[$field][$filter])) {
            unset($this->activeFilters[$field][$filter]);
            // si ya no quedan valores para ese campo, eliminamos el subarray
            if (empty($this->activeFilters[$field])) {
                unset($this->activeFilters[$field]);
            }
        } else {
            $this->activeFilters[$field][$filter] = true;
        }

        $this->resetPage();
    }
    
    public function mount()
    {
        // Redireccion si accedemos a '/tickets' por URL (sin llenar el formulario de usuario)
        if(request()->routeIs('tickets.user') && !request("user"))
    {
            return redirect()->to(route("bienvenida"));
        }

        // Si ingresamos como usuario
        if (request()->routeIs('tickets.user')) {
            $this->user = request("user");
        }

        $this->numEstados = count(Ticket::ESTADOS);
    }

    public function render()
    {
        // Crear consulta base
        $this->query = Ticket::query()->latest();

        // Filtramos por usuario si no se está autenticado
        if (!Auth::check()) {
            $this->query->where('correo', $this->user);
        }

        // Aplicar filtros activos
        if (!empty($this->activeFilters)) {
            foreach ($this->activeFilters as $field => $filters) {
                $this->query->whereNotIn($field, array_keys($filters));
            }
        }

        return view('livewire.tickets.ticket-index', [
            'tickets' => $this->query->paginate(10),
            'tipos' => Type::pluck('nombre', 'id')->toArray(),
            'areas' => Area::pluck('nombre', 'id')->toArray(),
            'estados' => Ticket::ESTADOS,
        ]);
    }
}
