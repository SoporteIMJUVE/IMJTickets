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
            <a href="#" class="px-4 py-2 bg-[#621132] text-white rounded flex items-center gap-2 hover:opacity-90 text-sm font-bold">
                <span class="material-symbols-outlined text-sm">add</span>
                Nuevo Ticket
            </a>
        </div>
    </div>

    {{-- KANBAN VIEW --}}
    <div id="view-kanban">
        <div class="grid grid-cols-3 gap-6">
            @php
                $columns = [
                    ['key' => 'abierto',     'label' => 'Abierto',     'color' => '#1E40AF', 'bg' => '#1E40AF/10', 'icon' => 'inbox'],
                    ['key' => 'atendiendo',  'label' => 'Atendiendo',  'color' => '#621132', 'bg' => '#D4C19C/30', 'icon' => 'pending_actions'],
                    ['key' => 'cerrado',     'label' => 'Cerrado',     'color' => '#166534', 'bg' => '#166534/10', 'icon' => 'task_alt'],
                ];
            @endphp
            @foreach($columns as $col)
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <div class="w-3 h-3 rounded-full" style="background:{{ $col['color'] }}"></div>
                        <h3 class="font-bold text-sm uppercase tracking-wider">{{ $col['label'] }}</h3>
                    </div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                          style="background:{{ $col['color'] }}15; color:{{ $col['color'] }}">0</span>
                </div>
                <div class="space-y-3 min-h-[200px]">
                    <div class="border-2 border-dashed border-[#E5E7EB] rounded-xl p-6 text-center text-[#544246]">
                        <span class="material-symbols-outlined text-2xl block mb-1">{{ $col['icon'] }}</span>
                        <p class="text-xs">Sin tickets</p>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    {{-- LIST VIEW --}}
    <div id="view-list" class="hidden">
        <div class="bg-white border border-[#E5E7EB] rounded-xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-[#E5E7EB] flex items-center justify-between">
                <h3 class="text-lg font-bold">Todos los Tickets</h3>
                <div class="flex gap-2">
                    <select class="bg-[#F3F4F6] border-none rounded-lg px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-[#621132]">
                        <option>Todos los estados</option>
                        <option>Abierto</option>
                        <option>Atendiendo</option>
                        <option>Cerrado</option>
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
                <tbody>
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center text-[#544246]">
                            <span class="material-symbols-outlined text-4xl block mb-2">confirmation_number</span>
                            <p class="text-sm">No hay tickets registrados</p>
                        </td>
                    </tr>
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
            <h3 class="text-xl font-bold mt-2">Detalle de Ticket</h3>
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
                <div class="col-span-2"><p class="text-[10px] text-[#544246] font-bold uppercase">Descripción</p><p id="panel-desc">—</p></div>
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
    const active = 'px-4 py-2 rounded-md text-sm font-bold transition-all bg-white text-[#621132] shadow-sm';
    const inactive = 'px-4 py-2 rounded-md text-sm font-bold transition-all text-[#544246] hover:bg-[#eae8e7]';
    document.getElementById('btn-kanban').className = isKanban ? active : inactive;
    document.getElementById('btn-list').className = !isKanban ? active : inactive;
}
function openTicketPanel(id) {
    document.getElementById('ticket-panel').classList.remove('closed');
    document.getElementById('ticket-backdrop').classList.remove('hidden');
    document.getElementById('panel-ticket-id').innerText = '#' + id;
}
function closeTicketPanel() {
    document.getElementById('ticket-panel').classList.add('closed');
    document.getElementById('ticket-backdrop').classList.add('hidden');
}
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeTicketPanel(); });
</script>
</x-layouts.app>
