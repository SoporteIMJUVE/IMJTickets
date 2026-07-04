<x-layouts.app title="Usuarios — IMJUVE CRM">
<div class="p-8 relative" id="crm-page">

    {{-- Header --}}
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="text-[32px] font-bold leading-10 tracking-tight text-[#621132]">Gestión de Usuarios</h2>
            <p class="text-[#544246] text-sm mt-1">Directorio institucional y asignación de activos.</p>
        </div>
        <div class="flex gap-3">
            <button class="px-4 py-2 border border-[#D4C19C] text-[#621132] rounded flex items-center gap-2 hover:bg-[#eae8e7] transition-colors text-sm font-bold">
                <span class="material-symbols-outlined text-sm">filter_list</span>
                Filtrar
            </button>
            <button class="px-4 py-2 bg-[#621132] text-white rounded flex items-center gap-2 hover:opacity-90 transition-opacity text-sm font-bold">
                <span class="material-symbols-outlined text-sm">person_add</span>
                Nuevo Usuario
            </button>
        </div>
    </div>

    {{-- KPI Bento --}}
    <div class="grid grid-cols-12 gap-4 mb-8">
        <div class="col-span-3 bg-white border border-[#E5E7EB] p-6 rounded-xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-[#166534]/10 text-[#166534] flex items-center justify-center">
                <span class="material-symbols-outlined">group</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246]">Usuarios Activos</p>
                <p class="text-2xl font-bold leading-tight">{{ $totalActivos }}</p>
            </div>
        </div>
        <div class="col-span-3 bg-white border border-[#E5E7EB] p-6 rounded-xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-[#1E40AF]/10 text-[#1E40AF] flex items-center justify-center">
                <span class="material-symbols-outlined">computer</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246]">Equipos Asignados</p>
                <p class="text-2xl font-bold leading-tight">{{ $totalEquipos }}</p>
            </div>
        </div>
        <div class="col-span-3 bg-white border border-[#E5E7EB] p-6 rounded-xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-[#D4C19C]/30 text-[#621132] flex items-center justify-center">
                <span class="material-symbols-outlined">apartment</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246]">Departamentos</p>
                <p class="text-2xl font-bold leading-tight">{{ $totalDeptos }}</p>
            </div>
        </div>
        <div class="col-span-3 bg-white border border-[#E5E7EB] p-6 rounded-xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-[#991B1B]/10 text-[#991B1B] flex items-center justify-center">
                <span class="material-symbols-outlined">person_off</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246]">Bajas</p>
                <p class="text-2xl font-bold leading-tight">{{ $totalBajas }}</p>
            </div>
        </div>
    </div>

    {{-- User Table --}}
    <div class="bg-white border border-[#E5E7EB] rounded-xl overflow-hidden shadow-sm">

        {{-- Search bar --}}
        <div class="px-6 py-4 border-b border-[#E5E7EB] flex items-center justify-between">
            <div class="relative w-72">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#544246] text-sm">search</span>
                <input class="w-full bg-[#F3F4F6] border-none rounded-lg pl-9 pr-4 py-2 text-sm focus:ring-2 focus:ring-[#621132] outline-none"
                       placeholder="Buscar por nombre o área..." type="text" id="crm-search">
            </div>
            <div class="flex items-center gap-2 text-sm text-[#544246]">
                <span class="material-symbols-outlined text-sm">filter_alt</span>
                <select class="bg-[#F3F4F6] border-none rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#621132] outline-none">
                    <option>Todos los departamentos</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="users-table">
                <thead class="bg-[#F3F4F6] border-b border-[#E5E7EB]">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Usuario</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Área / Dirección</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Equipo</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Extensión</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Estado</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246] text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E7EB]" id="users-tbody">
                    @forelse($empleados as $emp)
                    @php
                        $iniciales = strtoupper(
                            substr(trim($emp->nombre ?? 'U'), 0, 1) .
                            substr(trim($emp->apellido_paterno ?? ''), 0, 1)
                        );
                        $nombreCompleto = trim("{$emp->nombre} {$emp->apellido_paterno} {$emp->apellido_materno}");
                        $correo = strtolower(
                            iconv('UTF-8','ASCII//TRANSLIT', preg_replace('/\s+/','.',explode(' ', trim($emp->nombre))[0])) .
                            '.' . iconv('UTF-8','ASCII//TRANSLIT', $emp->apellido_paterno ?? 'imjuve')
                        ) . '@imjuve.gob.mx';
                    @endphp
                    <tr class="hover:bg-[#D4C19C]/5 transition-colors cursor-pointer group user-row"
                        data-search="{{ strtolower($nombreCompleto . ' ' . ($emp->departamento_nombre ?? '')) }}"
                        onclick="openUserPanel('{{ $emp->id_empleado }}')">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-[#D4C19C] flex items-center justify-center text-[#621132] font-bold text-xs flex-shrink-0">
                                    {{ $iniciales }}
                                </div>
                                <div>
                                    <p class="font-bold text-sm text-[#1b1c1c]">{{ $nombreCompleto }}</p>
                                    <p class="text-[11px] text-[#544246]">{{ $correo }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-[#544246]">{{ $emp->departamento_nombre ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if($emp->total_equipos > 0)
                            <div class="flex items-center gap-1 text-[#544246]">
                                <span class="material-symbols-outlined text-sm">laptop</span>
                                {{ $emp->total_equipos }} equipo{{ $emp->total_equipos > 1 ? 's' : '' }}
                            </div>
                            @else
                            <span class="text-[#544246]">Sin asignar</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-mono text-sm text-[#621132]">
                            {{ $emp->extension ? 'ext. ' . $emp->extension : '—' }}
                        </td>
                        <td class="px-6 py-4">
                            @if($emp->activo)
                            <span class="bg-[#166534]/10 text-[#166534] px-3 py-1 rounded-full text-[11px] font-bold uppercase">Activo</span>
                            @else
                            <span class="bg-[#991B1B]/10 text-[#991B1B] px-3 py-1 rounded-full text-[11px] font-bold uppercase">Baja</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button class="p-2 hover:bg-[#F3F4F6] rounded-full text-[#544246] group-hover:text-[#621132] transition-colors">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-[#544246] text-sm">
                            No hay empleados registrados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-[#E5E7EB] flex justify-between items-center bg-[#fbf9f8]">
            <p class="text-sm text-[#544246]">Mostrando datos de ejemplo</p>
            <div class="flex gap-2">
                <button class="w-8 h-8 flex items-center justify-center rounded border border-[#E5E7EB] hover:bg-[#F3F4F6]">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
                <button class="w-8 h-8 flex items-center justify-center rounded bg-[#621132] text-white font-bold text-sm">1</button>
                <button class="w-8 h-8 flex items-center justify-center rounded border border-[#E5E7EB] hover:bg-[#F3F4F6] font-bold text-sm">2</button>
                <button class="w-8 h-8 flex items-center justify-center rounded border border-[#E5E7EB] hover:bg-[#F3F4F6]">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Side Panel: User Detail (400px) --}}
<div class="fixed top-0 right-0 h-screen w-[400px] bg-white shadow-2xl border-l border-[#D4C19C] z-[60] detail-panel closed flex flex-col" id="user-panel">
    {{-- Header --}}
    <div class="p-6 border-b border-[#E5E7EB] bg-[#fbf9f8] flex justify-between items-start">
        <div>
            <div class="w-16 h-16 rounded-full bg-[#D4C19C] flex items-center justify-center text-[#621132] font-bold text-xl mb-3" id="panel-avatar">US</div>
            <h3 class="text-xl font-bold" id="panel-name">Usuario</h3>
            <p class="text-sm text-[#544246]" id="panel-area">Área</p>
        </div>
        <button class="p-2 hover:bg-[#F3F4F6] rounded-full transition-colors" onclick="closeUserPanel()">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    {{-- Tabs --}}
    <div class="flex border-b border-[#E5E7EB] px-6 bg-white">
        <button class="px-4 py-3 text-[#621132] font-bold border-b-2 border-[#621132] text-sm" onclick="switchPanelTab('recursos', this)">Recursos</button>
        <button class="px-4 py-3 text-[#544246] font-medium text-sm hover:text-[#621132]" onclick="switchPanelTab('historial', this)">Historial</button>
        <button class="px-4 py-3 text-[#544246] font-medium text-sm hover:text-[#621132]" onclick="switchPanelTab('tickets', this)">Tickets</button>
    </div>

    {{-- Scrollable Body --}}
    <div class="flex-1 overflow-y-auto custom-scrollbar">
        {{-- Tab: Recursos --}}
        <div class="p-6 space-y-6" id="tab-recursos">
            <section>
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">badge</span>
                    INFORMACIÓN DE CONTACTO
                </h4>
                <div class="grid grid-cols-2 gap-4 bg-[#F3F4F6] p-4 rounded-lg">
                    <div>
                        <p class="text-[10px] text-[#544246] font-bold uppercase">Correo</p>
                        <p class="text-sm font-semibold">—</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-[#544246] font-bold uppercase">Extensión</p>
                        <p class="text-sm font-semibold">—</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-[10px] text-[#544246] font-bold uppercase">Departamento</p>
                        <p class="text-sm font-semibold">—</p>
                    </div>
                </div>
            </section>

            <section>
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">computer</span>
                    EQUIPO ASIGNADO
                </h4>
                <div class="space-y-2">
                    <div class="flex items-center gap-3 p-3 border border-[#E5E7EB] rounded-lg">
                        <span class="material-symbols-outlined text-[#621132]">laptop</span>
                        <div>
                            <p class="text-sm font-bold">Laptop</p>
                            <p class="text-[11px] text-[#544246]">No hay equipo asignado</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 p-3 border border-[#E5E7EB] rounded-lg">
                        <span class="material-symbols-outlined text-[#544246]">print</span>
                        <div>
                            <p class="text-sm font-bold">Impresora</p>
                            <p class="text-[11px] text-[#544246]">No asignada</p>
                        </div>
                    </div>
                </div>
            </section>

            <section>
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">lan</span>
                    RED
                </h4>
                <div class="bg-[#621132] text-white p-4 rounded-lg space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold opacity-70 uppercase">IPv4</span>
                        <span class="font-mono text-sm">—</span>
                    </div>
                    <div class="h-px bg-white/20"></div>
                    <div class="flex justify-between items-center">
                        <span class="text-[10px] font-bold opacity-70 uppercase">MAC</span>
                        <span class="font-mono text-sm">—</span>
                    </div>
                </div>
            </section>
        </div>

        {{-- Tab: Historial --}}
        <div class="p-6 space-y-4 hidden" id="tab-historial">
            <div class="text-center text-[#544246] py-12">
                <span class="material-symbols-outlined text-4xl mb-2 block">history</span>
                <p class="text-sm">No hay historial disponible</p>
            </div>
        </div>

        {{-- Tab: Tickets --}}
        <div class="p-6 space-y-4 hidden" id="tab-tickets">
            <div class="text-center text-[#544246] py-12">
                <span class="material-symbols-outlined text-4xl mb-2 block">confirmation_number</span>
                <p class="text-sm">No hay tickets recientes</p>
            </div>
        </div>
    </div>

    {{-- Footer Actions --}}
    <div class="p-6 border-t border-[#E5E7EB] bg-[#fbf9f8] grid grid-cols-2 gap-3">
        <button class="w-full py-3 bg-[#F3F4F6] text-[#621132] font-bold rounded-lg hover:bg-[#eae8e7] transition-colors flex items-center justify-center gap-2 text-sm">
            <span class="material-symbols-outlined text-sm">edit</span>
            Editar
        </button>
        <button class="w-full py-3 bg-[#621132] text-white font-bold rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center gap-2 text-sm">
            <span class="material-symbols-outlined text-sm">picture_as_pdf</span>
            Resguardo
        </button>
    </div>
</div>

{{-- Backdrop --}}
<div class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[55] hidden" id="panel-backdrop" onclick="closeUserPanel()"></div>

<script>
function openUserPanel(userId) {
    document.getElementById('user-panel').classList.remove('closed');
    document.getElementById('panel-backdrop').classList.remove('hidden');
}

function closeUserPanel() {
    document.getElementById('user-panel').classList.add('closed');
    document.getElementById('panel-backdrop').classList.add('hidden');
}

function switchPanelTab(tab, btn) {
    ['recursos', 'historial', 'tickets'].forEach(t => {
        document.getElementById('tab-' + t).classList.add('hidden');
    });
    document.querySelectorAll('#user-panel .flex.border-b button').forEach(b => {
        b.className = 'px-4 py-3 text-[#544246] font-medium text-sm hover:text-[#621132]';
    });
    document.getElementById('tab-' + tab).classList.remove('hidden');
    btn.className = 'px-4 py-3 text-[#621132] font-bold border-b-2 border-[#621132] text-sm';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeUserPanel();
});

// Live search filter
document.getElementById('crm-search').addEventListener('input', (e) => {
    const term = e.target.value.toLowerCase();
    document.querySelectorAll('#users-tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(term) ? '' : 'none';
    });
});
</script>
</x-layouts.app>
