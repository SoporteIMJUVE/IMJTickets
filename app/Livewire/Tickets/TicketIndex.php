<?php

namespace App\Livewire\Tickets;

use Livewire\Component;
use App\Models\Ticket;
use App\Models\Area;
use App\Models\Type;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TicketsExcel;
use Livewire\Attributes\On;

use Livewire\WithPagination;

use Illuminate\Support\Facades\Auth;

class TicketIndex extends Component
{
    use WithPagination;

    public $user;
    public $numEstados;
    public array $activeFilters = [];
    public array $period = [];
    public array $order = ['created_at', 'desc'];

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

    public function setOrder($field, $direction)
    {
        $this->order = [(string) $field, (string) $direction];
        $this->resetPage();
    }

    public function buildQuery()
    {
        // Crear consulta base
        $query = Ticket::query();

        // Filtramos por usuario si no se está autenticado
        if (!Auth::check()) {
            $query->where('correo', $this->user);
        }

        // Aplicar filtros activos si los hay
        if (!empty($this->activeFilters)) {
            foreach ($this->activeFilters as $field => $filters) {
                $query->whereNotIn($field, array_keys($filters));
            }
        }

        // Aplicar filtro de periodo si los hay
        if (!empty($this->period)) {
            foreach ($this->period as $field => $range) {
                if(!empty($range) && isset($range['from']) && $range['from'] != null && isset($range['to']) && $range['to'] != null) {

                    if($field === 'updated_at') {
                        $query->where('estado', 2); // Solo filtrar tickets atendidos
                    }

                    $query->whereBetween($field, [$range['from'], $range['to']]);
                }
            }
        }

        // Aplicar ordenamiento
        if($this->order[0] === 'created_at'){
            $query->orderBy($this->order[0], $this->order[1]); // Ordenamiento simple por fecha de creacion
        } else {
            $query
                ->orderByRaw('(CASE WHEN estado IS ' . ($this->order[0] === 'atendido_at' ? 'NOT 0' : 2) . ' THEN 0 ELSE 1 END) ASC')
                ->orderBy($this->order[0], $this->order[1])
                ->orderBy('created_at', $this->order[1]);
        }

        return $query;
    }

    #[On('emitExcel')]
    public function exportExcel()
    {
        return Excel::download(new TicketsExcel($this->buildQuery()->get()), 'IMJTickets '.now().'.xlsx');
    }
    
    public function mount()
    {
        // Redireccion si accedemos a '/tickets' por URL (sin llenar el formulario de usuario)
        if(request()->routeIs('tickets.user') && !request("user")){
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
        return view('livewire.tickets.ticket-index', [
            'tickets' => $this->buildQuery()->paginate(10),
            'tipos' => Type::pluck('nombre', 'id')->toArray(),
            'areas' => Area::pluck('nombre', 'id')->toArray(),
            'estados' => Ticket::ESTADOS,
        ]);
    }
}
