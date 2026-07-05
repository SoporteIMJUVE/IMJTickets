<x-layouts.app title="Tickets — IMJUVE CRM">
<div class="p-8" id="tickets-page">

    {{-- Header + view toggle --}}
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="text-[32px] font-bold leading-10 tracking-tight text-[#621132]">Gestión de Tickets</h2>
            <p class="text-[#544246] text-sm mt-1">Soporte técnico y seguimiento de incidencias.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex bg-[#F3F4F6] rounded-lg p-1">
                <button onclick="setView('kanban')" id="btn-kanban"
                        class="px-4 py-2 rounded-md text-sm font-bold transition-all bg-white text-[#621132] shadow-sm">
                    <span class="material-symbols-outlined text-sm align-middle">view_kanban</span>
                    Kanban
                </button>
                <button onclick="setView('list')" id="btn-list"
                        class="px-4 py-2 rounded-md text-sm font-bold transition-all text-[#544246] hover:bg-[#eae8e7]">
                    <span class="material-symbols-outlined text-sm align-middle">format_list_bulleted</span>
                    Lista
                </button>
            </div>
            <a href="{{ route('tickets.create') }}" class="px-4 py-2 bg-[#621132] text-white rounded flex items-center gap-2 hover:opacity-90 text-sm font-bold">
                <span class="material-symbols-outlined text-sm">add</span>
                Nuevo Ticket
            </a>
        </div>
    </div>

    {{-- KANBAN VIEW --}}
    <div id="view-kanban">
        @php
            $columns = [
                ['estado' => 0, 'label' => 'Abierto',    'color' => '#1E40AF', 'icon' => 'inbox'],
                ['estado' => 1, 'label' => 'Atendiendo', 'color' => '#621132', 'icon' => 'pending_actions'],
                ['estado' => 2, 'label' => 'Cerrado',    'color' => '#166534', 'icon' => 'task_alt'],
            ];
        @endphp
        <div class="grid grid-cols-3 gap-6">
            @foreach($columns as $col)
            @php $colTickets = $porEstado[$col['estado']]; @endphp
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full" style="background:{{ $col['color'] }}"></div>
                        <h3 class="font-bold text-sm uppercase tracking-wider">{{ $col['label'] }}</h3>
                    </div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                          style="background:{{ $col['color'] }}15; color:{{ $col['color'] }}">
                        {{ $colTickets->count() }}
                    </span>
                </div>
                <div class="space-y-3 min-h-[200px]">
                    @forelse($colTickets as $t)
                    <div class="bg-white rounded-xl border border-[#E5E7EB] p-4 shadow-sm hover:shadow-md transition-shadow cursor-pointer"
                         data-ticket="{{ e(json_encode(['id'=>$t->id,'nombre'=>$t->nombre,'area'=>$t->area,'tipo'=>$t->tipo,'descripcion'=>$t->descripcion,'estado'=>$t->estado])) }}"
                         onclick="openTicketPanel(this)">
                        <div class="flex justify-between items-start mb-2">
                            <span class="font-mono text-[10px] font-bold px-2 py-0.5 rounded"
                                  style="color:{{ $col['color'] }};background:{{ $col['color'] }}15">
                                #{{ $t->id }}
                            </span>
                            <span class="text-[10px] text-[#544246]">
                                {{ \Carbon\Carbon::parse($t->created_at)->format('d/m/Y') }}
                            </span>
                        </div>
                        <p class="text-sm font-bold text-[#1b1c1c] mb-1 truncate">{{ $t->tipo }}</p>
                        <p class="text-[11px] text-[#544246] line-clamp-2 mb-3">{{ $t->descripcion }}</p>
                        <div class="flex items-center gap-2 pt-3 border-t border-[#E5E7EB]">
                            <div class="w-6 h-6 rounded-full bg-[#D4C19C] text-[#621132] flex items-center justify-center text-[9px] font-bold flex-shrink-0">
                                {{ strtoupper(substr($t->nombre ?? 'U', 0, 1)) }}
                            </div>
                            <span class="text-[10px] text-[#544246] truncate">{{ $t->nombre }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="border-2 border-dashed border-[#E5E7EB] rounded-xl p-6 text-center text-[#544246]">
                        <span class="material-symbols-outlined text-2xl block mb-1">{{ $col['icon'] }}</span>
                        <p class="text-xs">Sin tickets</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- LIST VIEW --}}
    <div id="view-list" class="hidden">
        <div class="bg-white border border-[#E5E7EB] rounded-xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-[#E5E7EB] flex items-center justify-between">
                <h3 class="text-lg font-bold">Todos los Tickets <span class="text-sm font-normal text-[#544246]">({{ $tickets->count() }})</span></h3>
                <div class="flex gap-2">
                    <select id="filtro-estado" onchange="filtrarLista()" class="bg-[#F3F4F6] border-none rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#621132]">
                        <option value="">Todos los estados</option>
                        <option value="0">Abierto</option>
                        <option value="1">Atendiendo</option>
                        <option value="2">Cerrado</option>
                    </select>
                </div>
            </div>
            <table class="w-full text-left">
                <thead class="bg-[#F3F4F6] border-b border-[#E5E7EB]">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">#</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Solicitante</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Tipo</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Área</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Estado</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Fecha</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246] text-right">Ver</th>
                    </tr>
                </thead>
                <tbody id="list-tbody">
                    @forelse($tickets as $t)
                    @php
                        $badgeConf = match($t->estado) {
                            0 => ['label' => 'Abierto',    'style' => 'background:#DBEAFE;color:#1E40AF'],
                            1 => ['label' => 'Atendiendo', 'style' => 'background:#FEF3C7;color:#92400E'],
                            2 => ['label' => 'Cerrado',    'style' => 'background:#DCFCE7;color:#166534'],
                            default => ['label' => '—', 'style' => ''],
                        };
                    @endphp
                    <tr class="border-b border-[#E5E7EB] hover:bg-[#D4C19C]/5 cursor-pointer list-row"
                        data-estado="{{ $t->estado }}"
                        data-ticket="{{ e(json_encode(['id'=>$t->id,'nombre'=>$t->nombre,'area'=>$t->area,'tipo'=>$t->tipo,'descripcion'=>$t->descripcion,'estado'=>$t->estado])) }}"
                        onclick="openTicketPanel(this)">
                        <td class="px-6 py-4 font-mono text-xs text-[#621132] font-bold">#{{ $t->id }}</td>
                        <td class="px-6 py-4 text-sm font-semibold">{{ $t->nombre }}</td>
                        <td class="px-6 py-4 text-sm text-[#544246]">{{ $t->tipo }}</td>
                        <td class="px-6 py-4 text-sm text-[#544246]">{{ $t->area }}</td>
                        <td class="px-6 py-4">
                            <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase"
                                  style="{{ $badgeConf['style'] }}">
                                {{ $badgeConf['label'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-[#544246]">
                            {{ \Carbon\Carbon::parse($t->created_at)->format('d/m/Y') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button class="p-2 hover:bg-[#F3F4F6] rounded-full text-[#544246]"
                                    onclick="event.stopPropagation(); openTicketPanel(this.closest('tr'))">
                                <span class="material-symbols-outlined text-sm">open_in_new</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center text-[#544246]">
                            <span class="material-symbols-outlined text-4xl block mb-2">confirmation_number</span>
                            <p class="text-sm">No hay tickets registrados</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Side Panel: Ticket Detail --}}
<div class="fixed top-0 right-0 h-screen w-[400px] bg-white shadow-2xl border-l border-[#D4C19C] z-[60] detail-panel closed flex flex-col" id="ticket-panel">
    <div class="p-6 border-b border-[#E5E7EB] bg-[#fbf9f8] flex justify-between items-start">
        <div>
            <span class="font-mono text-xs text-[#621132] bg-[#621132]/10 px-2 py-1 rounded" id="panel-ticket-id">#—</span>
            <h3 class="text-xl font-bold mt-2" id="panel-tipo">Detalle de Ticket</h3>
        </div>
        <button class="p-2 hover:bg-[#F3F4F6] rounded-full transition-colors" onclick="closeTicketPanel()">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-6">
        <section>
            <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-4">DETALLES</h4>
            <div class="grid grid-cols-2 gap-4 bg-[#F3F4F6] p-4 rounded-lg text-sm">
                <div><p class="text-[10px] text-[#544246] font-bold uppercase">Solicitante</p><p class="font-semibold" id="panel-nombre">—</p></div>
                <div><p class="text-[10px] text-[#544246] font-bold uppercase">Área</p><p class="font-semibold" id="panel-area">—</p></div>
                <div class="col-span-2"><p class="text-[10px] text-[#544246] font-bold uppercase">Descripción</p><p id="panel-desc" class="text-[#544246]">—</p></div>
            </div>
        </section>
        <section>
            <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-4">CAMBIAR ESTADO</h4>
            <div class="flex gap-2">
                <button class="flex-1 py-2 text-sm font-bold border border-[#E5E7EB] rounded hover:bg-[#F3F4F6]">Abierto</button>
                <button class="flex-1 py-2 text-sm font-bold border border-[#D4C19C] rounded text-[#621132] hover:bg-[#D4C19C]/10">Atendiendo</button>
                <button class="flex-1 py-2 text-sm font-bold bg-[#166534] text-white rounded hover:opacity-90">Cerrado</button>
            </div>
        </section>
    </div>

    <div class="p-6 border-t border-[#E5E7EB] grid grid-cols-2 gap-3">
        <button class="py-3 bg-[#F3F4F6] text-[#621132] font-bold rounded-lg hover:bg-[#eae8e7] text-sm">Comentar</button>
        <button class="py-3 bg-[#621132] text-white font-bold rounded-lg hover:opacity-90 text-sm">Guardar</button>
    </div>
</div>

<div class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[55] hidden" id="ticket-backdrop" onclick="closeTicketPanel()"></div>

<script>
function setView(mode) {
    const isKanban = mode === 'kanban';
    document.getElementById('view-kanban').classList.toggle('hidden', !isKanban);
    document.getElementById('view-list').classList.toggle('hidden', isKanban);
    const active   = 'px-4 py-2 rounded-md text-sm font-bold transition-all bg-white text-[#621132] shadow-sm';
    const inactive = 'px-4 py-2 rounded-md text-sm font-bold transition-all text-[#544246] hover:bg-[#eae8e7]';
    document.getElementById('btn-kanban').className = isKanban ? active : inactive;
    document.getElementById('btn-list').className   = !isKanban ? active : inactive;
}

function filtrarLista() {
    const val = document.getElementById('filtro-estado').value;
    document.querySelectorAll('#list-tbody .list-row').forEach(row => {
        row.style.display = (!val || row.dataset.estado === val) ? '' : 'none';
    });
}

function openTicketPanel(el) {
    const data = JSON.parse(el.getAttribute('data-ticket'));
    document.getElementById('panel-ticket-id').innerText = '#' + data.id;
    document.getElementById('panel-tipo').innerText      = data.tipo;
    document.getElementById('panel-nombre').innerText   = data.nombre;
    document.getElementById('panel-area').innerText     = data.area;
    document.getElementById('panel-desc').innerText     = data.descripcion;
    document.getElementById('ticket-panel').classList.remove('closed');
    document.getElementById('ticket-backdrop').classList.remove('hidden');
}

function closeTicketPanel() {
    document.getElementById('ticket-panel').classList.add('closed');
    document.getElementById('ticket-backdrop').classList.add('hidden');
}

document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeTicketPanel(); });
</script>
</x-layouts.app>
