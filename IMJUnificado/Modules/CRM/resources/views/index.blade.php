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
            <button onclick="openNuevoModal()" class="px-4 py-2 bg-[#621132] text-white rounded flex items-center gap-2 hover:opacity-90 transition-opacity text-sm font-bold">
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
        <div class="px-6 py-4 border-b border-[#E5E7EB] flex items-center justify-between gap-4">
            <div class="relative w-72 shrink-0">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#544246] text-sm">search</span>
                <input class="w-full bg-[#F3F4F6] border-none rounded-lg pl-9 pr-4 py-2 text-sm focus:ring-2 focus:ring-[#621132] outline-none"
                       placeholder="Buscar nombre, puesto..." type="text" id="crm-search">
            </div>
            <div class="flex items-center gap-3 text-sm text-[#544246]">
                <select id="filter-depa" class="bg-[#F3F4F6] border-none rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#621132] outline-none">
                    <option value="">Todos los departamentos</option>
                    @foreach($departamentos as $d)
                    <option value="{{ strtolower($d->nombre) }}">{{ $d->nombre }}</option>
                    @endforeach
                </select>
                <select id="filter-estado" class="bg-[#F3F4F6] border-none rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#621132] outline-none">
                    <option value="">Todos</option>
                    <option value="1">Activos</option>
                    <option value="0">Bajas</option>
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
                        data-id="{{ $emp->id_empleado }}"
                        data-activo="{{ $emp->activo ? '1' : '0' }}"
                        data-search="{{ strtolower($nombreCompleto . ' ' . ($emp->puesto ?? '') . ' ' . ($emp->departamento_nombre ?? '')) }}"
                        data-nombre="{{ $nombreCompleto }}"
                        data-nombre-raw="{{ $emp->nombre }}"
                        data-ap="{{ $emp->apellido_paterno ?? '' }}"
                        data-am="{{ $emp->apellido_materno ?? '' }}"
                        data-puesto="{{ $emp->puesto ?? '' }}"
                        data-correo="{{ $emp->correo ?? '' }}"
                        data-depa="{{ strtolower($emp->departamento_nombre ?? '') }}"
                        data-depa-label="{{ $emp->departamento_nombre ?? '' }}"
                        data-depa-id="{{ $emp->id_departamento ?? '' }}"
                        data-ext="{{ $emp->extension ?? '' }}"
                        data-equipos="{{ $emp->total_equipos }}"
                        data-iniciales="{{ $iniciales }}"
                        onclick="openUserPanel(this)">
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
            <p class="text-sm text-[#544246]" id="tabla-conteo">{{ $empleados->count() }} registro(s)</p>
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
            <div class="flex items-center gap-2 mb-1">
                <h3 class="text-xl font-bold" id="panel-name">Usuario</h3>
                <span id="panel-estado-badge" class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full"></span>
            </div>
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
                        <p class="text-sm font-semibold break-all" id="panel-correo">—</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-[#544246] font-bold uppercase">Extensión</p>
                        <p class="text-sm font-semibold" id="panel-ext">—</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-[10px] text-[#544246] font-bold uppercase">Departamento</p>
                        <p class="text-sm font-semibold" id="panel-depa">—</p>
                    </div>
                </div>
            </section>

            <section>
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">computer</span>
                    EQUIPOS ASIGNADOS
                    <span class="ml-auto font-mono text-[#621132]" id="panel-eq-count"></span>
                </h4>
                <div id="panel-equipos-list">
                    <div class="flex items-center justify-center py-8 text-[#544246] text-xs">
                        <span class="material-symbols-outlined text-2xl animate-spin mr-2">progress_activity</span>
                        Cargando…
                    </div>
                </div>
            </section>
        </div>

        {{-- Tab: Historial — todos los tickets del empleado --}}
        <div class="p-6 hidden" id="tab-historial">
            <div id="historial-content">
                <div class="text-center text-[#544246] py-12">
                    <span class="material-symbols-outlined text-4xl mb-2 block">history</span>
                    <p class="text-sm">Sin historial de tickets</p>
                </div>
            </div>
        </div>

        {{-- Tab: Tickets — solo tickets activos (Abierto / Atendiendo) --}}
        <div class="p-6 hidden" id="tab-tickets">
            <div id="tickets-activos-content">
                <div class="text-center text-[#544246] py-12">
                    <span class="material-symbols-outlined text-4xl mb-2 block">confirmation_number</span>
                    <p class="text-sm">Sin tickets activos</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer Actions --}}
    <div class="p-6 border-t border-[#E5E7EB] bg-[#fbf9f8] grid grid-cols-2 gap-3">
        <button onclick="openEditModal()" class="w-full py-3 bg-[#F3F4F6] text-[#621132] font-bold rounded-lg hover:bg-[#eae8e7] transition-colors flex items-center justify-center gap-2 text-sm">
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
// Tickets de todos los empleados indexados por correo
// Patrón inter-módulo: datos cargados desde el controlador con DB::table('tickets'),
// sin importar nada del módulo Tickets.
const ticketsPorCorreo = {!! json_encode($ticketsPorCorreo, JSON_HEX_TAG) !!};

let currentEmpleado = {};

async function openUserPanel(row) {
    currentEmpleado = {
        id:       row.dataset.id,
        activo:   row.dataset.activo === '1',
        nombre:   row.dataset.nombre,
        nombreRaw: row.dataset.nombreRaw,
        ap:       row.dataset.ap,
        am:       row.dataset.am,
        puesto:   row.dataset.puesto,
        correo:   row.dataset.correo,
        depa:     row.dataset.depaLabel,
        depaId:   row.dataset.depaId,
        ext:      row.dataset.ext,
        iniciales: row.dataset.iniciales,
    };

    const { nombre, correo, depa, ext, iniciales, activo } = currentEmpleado;

    // Header del panel
    document.getElementById('panel-avatar').innerText = iniciales;
    document.getElementById('panel-name').innerText   = nombre;
    document.getElementById('panel-area').innerText   = depa || '—';
    const badge = document.getElementById('panel-estado-badge');
    if (activo) {
        badge.textContent = 'Activo';
        badge.style.cssText = 'background-color:rgba(22,101,52,.1);color:#166534';
    } else {
        badge.textContent = 'Baja';
        badge.style.cssText = 'background-color:rgba(153,27,27,.1);color:#991B1B';
    }

    // Tab Recursos — datos de contacto
    document.getElementById('panel-correo').innerText = correo || '—';
    document.getElementById('panel-ext').innerText    = ext ? 'ext. ' + ext : '—';
    document.getElementById('panel-depa').innerText   = depa || '—';

    // Equipos: spinner mientras carga
    document.getElementById('panel-equipos-list').innerHTML =
        '<div class="flex items-center py-6 text-[#544246] text-xs gap-2"><span class="material-symbols-outlined text-xl animate-spin">progress_activity</span>Cargando equipos…</div>';
    document.getElementById('panel-eq-count').textContent = '';

    // Tabs Historial y Tickets — datos cruzados con el módulo Tickets
    const todos   = ticketsPorCorreo[correo] || [];
    const activos = todos.filter(t => t.estado < 2);

    document.getElementById('historial-content').innerHTML = todos.length
        ? todos.map(renderTicketItem).join('')
        : emptyState('history', 'Sin historial de tickets');

    document.getElementById('tickets-activos-content').innerHTML = activos.length
        ? activos.map(renderTicketItem).join('')
        : emptyState('confirmation_number', 'Sin tickets activos');

    // Volver al tab Recursos por defecto
    switchPanelTab('recursos', document.querySelector('#user-panel .flex.border-b button'));

    document.getElementById('user-panel').classList.remove('closed');
    document.getElementById('panel-backdrop').classList.remove('hidden');

    // Cargar equipos del empleado via AJAX
    try {
        const equipos = await fetch(`/crm/empleado/${currentEmpleado.id}/equipos`).then(r => r.json());
        renderEquiposPanel(equipos);
    } catch (e) {
        document.getElementById('panel-equipos-list').innerHTML =
            '<p class="text-xs text-red-500">Error al cargar equipos.</p>';
    }
}

function renderEquiposPanel(equipos) {
    const list = document.getElementById('panel-equipos-list');
    const count = document.getElementById('panel-eq-count');

    if (!equipos.length) {
        count.textContent = '';
        list.innerHTML = `<div class="flex flex-col items-center py-8 text-[#544246]">
            <span class="material-symbols-outlined text-3xl mb-2 opacity-40">computer_off</span>
            <p class="text-xs">Sin equipos asignados</p>
        </div>`;
        return;
    }

    count.textContent = equipos.length + ' equipo' + (equipos.length > 1 ? 's' : '');

    const tipoIcon  = { 'Laptop':'laptop', 'PC Avanzada':'computer', 'PC Especializada':'developer_board' };
    const tipoColor = { 'Laptop':'#1E40AF', 'PC Avanzada':'#166534', 'PC Especializada':'#9A3412' };

    list.innerHTML = equipos.map(eq => {
        const icon  = tipoIcon[eq.tipo]  ?? 'computer';
        const color = tipoColor[eq.tipo] ?? '#621132';
        const marca = [eq.cpu_marca, eq.cpu_modelo].filter(Boolean).join(' ') || '—';
        const serie = eq.cpu_serie || '—';
        const ip    = eq.ipv4 || eq.ipv4_actual || null;
        const esFk  = eq.match === 'fk';

        // Badge de vinculación
        const matchBadge = esFk
            ? `<span title="Vinculado formalmente (FK)" style="background:#16653410;color:#166534;font-size:9px;font-weight:700;padding:1px 6px;border-radius:999px;letter-spacing:.04em">● VINCULADO</span>`
            : `<span title="Coincidencia por nombre — vincula desde Kardex" style="background:#92400E18;color:#92400E;font-size:9px;font-weight:700;padding:1px 6px;border-radius:999px;letter-spacing:.04em">⚠ POR NOMBRE</span>`;

        return `<div class="border rounded-lg p-3 mb-2 last:mb-0 transition-colors ${esFk ? 'border-[#166534]/30 hover:border-[#166534]/60' : 'border-[#E5E7EB] hover:border-[#D4C19C]'}">
            <div class="flex items-center gap-2 mb-2">
                <span class="material-symbols-outlined text-lg" style="color:${color}">${icon}</span>
                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full"
                      style="background:${color}1a;color:${color}">${escHtml(eq.tipo)}</span>
                ${matchBadge}
                ${eq.num_inventario ? `<span class="ml-auto text-[10px] font-mono text-[#544246] shrink-0">Inv.&nbsp;${escHtml(String(eq.num_inventario))}</span>` : ''}
            </div>
            <p class="text-sm font-bold text-[#1b1c1c] mb-2">${escHtml(marca)}</p>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                <div>
                    <p class="text-[10px] text-[#544246] font-bold uppercase">No. Serie</p>
                    <p class="font-mono text-xs text-[#621132]">${escHtml(serie)}</p>
                </div>
                <div>
                    <p class="text-[10px] text-[#544246] font-bold uppercase">IPv4</p>
                    ${ip
                        ? `<p class="font-mono text-xs text-[#621132]">${escHtml(ip)}</p>`
                        : `<p class="text-xs text-[#544246] opacity-40">Sin IP</p>`
                    }
                </div>
                ${eq.mac ? `<div class="col-span-2 mt-1">
                    <p class="text-[10px] text-[#544246] font-bold uppercase">MAC</p>
                    <p class="font-mono text-[11px] text-[#544246]">${escHtml(eq.mac)}</p>
                </div>` : ''}
            </div>
        </div>`;
    }).join('');
}

function renderTicketItem(t) {
    const label = {0:'Abierto',1:'Atendiendo',2:'Cerrado'}[t.estado] ?? '—';
    const style = {
        0: 'background:#DBEAFE;color:#1E40AF',
        1: 'background:#FEF3C7;color:#92400E',
        2: 'background:#DCFCE7;color:#166534',
    }[t.estado] ?? '';
    const fecha = t.fecha
        ? new Date(t.fecha).toLocaleDateString('es-MX', {day:'2-digit', month:'short', year:'numeric'})
        : '';
    return `<div class="border border-[#E5E7EB] rounded-lg p-4 mb-3 last:mb-0">
        <div class="flex justify-between items-center mb-2">
            <span style="color:#621132;background:#62113215;padding:2px 8px;border-radius:4px;font-family:monospace;font-size:10px;font-weight:700">#${t.id}</span>
            <span style="${style};padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700">${label}</span>
        </div>
        <p style="font-size:13px;font-weight:700;color:#1b1c1c;margin-bottom:4px">${escHtml(t.tipo)}</p>
        <p style="font-size:11px;color:#544246">${escHtml(t.descripcion)}</p>
        <p style="font-size:10px;color:#544246;margin-top:6px;opacity:.7">${fecha}</p>
    </div>`;
}

function emptyState(icon, msg) {
    return `<div style="text-align:center;color:#544246;padding:48px 0">
        <span class="material-symbols-outlined" style="font-size:36px;display:block;margin-bottom:8px">${icon}</span>
        <p style="font-size:13px">${msg}</p>
    </div>`;
}

function escHtml(s) {
    return String(s ?? '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
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
    if (e.key === 'Escape') { closeUserPanel(); closeNuevoModal(); }
});

function aplicarFiltros() {
    const term   = document.getElementById('crm-search').value.toLowerCase();
    const depa   = document.getElementById('filter-depa').value.toLowerCase();
    const estado = document.getElementById('filter-estado').value;
    let visibles = 0;

    document.querySelectorAll('#users-tbody tr.user-row').forEach(row => {
        const matchText   = !term   || row.dataset.search.includes(term);
        const matchDepa   = !depa   || row.dataset.depa === depa;
        const matchEstado = estado === '' || row.dataset.activo === estado;
        const visible     = matchText && matchDepa && matchEstado;
        row.style.display = visible ? '' : 'none';
        if (visible) visibles++;
    });

    const c = document.getElementById('tabla-conteo');
    if (c) c.textContent = visibles + ' registro(s)';
}

document.getElementById('crm-search').addEventListener('input', aplicarFiltros);
document.getElementById('filter-depa').addEventListener('change', aplicarFiltros);
document.getElementById('filter-estado').addEventListener('change', aplicarFiltros);

// ─── Modal Editar Usuario ────────────────────────────────────────────────────
function openEditModal() {
    if (!currentEmpleado.id) return;
    const e = currentEmpleado;

    document.getElementById('edit-id').value     = e.id;
    document.getElementById('edit-nombre').value = e.nombreRaw || '';
    document.getElementById('edit-ap').value     = e.ap || '';
    document.getElementById('edit-am').value     = e.am || '';
    document.getElementById('edit-puesto').value = e.puesto || '';
    document.getElementById('edit-correo').value = e.correo || '';

    const sel = document.getElementById('edit-depa');
    for (const opt of sel.options) opt.selected = opt.value === String(e.depaId);

    const btnBaja = document.getElementById('btn-baja-toggle');
    if (e.activo) {
        btnBaja.textContent = 'Dar de Baja';
        btnBaja.className   = 'w-full py-2.5 border border-[#991B1B] text-[#991B1B] font-bold rounded-lg hover:bg-red-50 transition-colors text-sm';
    } else {
        btnBaja.textContent = 'Reactivar Usuario';
        btnBaja.className   = 'w-full py-2.5 border border-[#166534] text-[#166534] font-bold rounded-lg hover:bg-green-50 transition-colors text-sm';
    }

    document.getElementById('modal-edit').classList.remove('hidden');
    document.getElementById('modal-edit').classList.add('flex');
}
function closeEditModal() {
    document.getElementById('modal-edit').classList.add('hidden');
    document.getElementById('modal-edit').classList.remove('flex');
}

document.addEventListener('DOMContentLoaded', () => {
    const CSRF = '{{ csrf_token() }}';

    document.getElementById('form-edit-usuario')?.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn  = document.getElementById('btn-guardar-edit');
        const orig = btn.textContent;
        btn.disabled = true; btn.textContent = 'Guardando…';

        const id = document.getElementById('edit-id').value;
        const payload = {
            nombre:           document.getElementById('edit-nombre').value,
            apellido_paterno: document.getElementById('edit-ap').value,
            apellido_materno: document.getElementById('edit-am').value,
            puesto:           document.getElementById('edit-puesto').value,
            correo:           document.getElementById('edit-correo').value,
            id_departamento:  document.getElementById('edit-depa').value || null,
        };

        try {
            const r = await fetch(`/crm/empleados/${id}`, {
                method: 'PATCH',
                headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF,
                           'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' },
                body: JSON.stringify(payload),
            });
            const data = await r.json();
            if (r.ok && data.ok) { closeEditModal(); window.location.reload(); }
            else {
                const msgs = data.errors ? Object.values(data.errors).flat().join('\n') : (data.message || 'Error.');
                alert(msgs);
            }
        } catch (err) { alert('Error de conexión.'); }
        finally { btn.disabled = false; btn.textContent = orig; }
    });

    document.getElementById('btn-baja-toggle')?.addEventListener('click', async () => {
        const id     = document.getElementById('edit-id').value;
        const activo = currentEmpleado.activo;
        const url    = activo ? `/crm/empleados/${id}` : `/crm/empleados/${id}/reactivar`;
        const method = activo ? 'DELETE' : 'PATCH';
        if (!confirm(activo ? '¿Confirmas dar de baja a este usuario?' : '¿Reactivar este usuario?')) return;

        const r = await fetch(url, {
            method, headers: { 'X-CSRF-TOKEN':CSRF, 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' },
        });
        if ((await r.json()).ok) { closeEditModal(); window.location.reload(); }
    });
});

// ─── Modal Nuevo Usuario ────────────────────────────────────────────────────
function openNuevoModal() {
    document.getElementById('modal-nuevo').classList.remove('hidden');
    document.getElementById('modal-nuevo').classList.add('flex');
}
function closeNuevoModal() {
    document.getElementById('modal-nuevo').classList.add('hidden');
    document.getElementById('modal-nuevo').classList.remove('flex');
    document.getElementById('form-nuevo-usuario').reset();
    document.getElementById('equipos-container').innerHTML = '';
    equipoIdx = 0;
}

let equipoIdx = 0;

function camposEquipo(idx, tipo) {
    const f = (name, label, placeholder = '', extra = '') =>
        `<div>
            <label class="block text-xs font-bold text-[#544246] mb-1">${label}</label>
            <input type="text" name="equipos[${idx}][${name}]" placeholder="${placeholder}" ${extra}
                class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
        </div>`;

    // Campos comunes a todos los tipos
    let html = `
        <div class="grid grid-cols-2 gap-3">
            ${f('nombre_equipo', 'Nombre del equipo', 'IMJUVE-LAP-001')}
            <div>
                <label class="block text-xs font-bold text-[#544246] mb-1">Marca CPU</label>
                <input type="text" name="equipos[${idx}][cpu_marca]" placeholder="Dell / HP / Lenovo"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
            </div>
            ${f('cpu_modelo', 'Modelo CPU', 'Latitude 5540')}
            ${f('cpu_serie', 'No. Serie CPU')}
            ${f('ipv4',      'IPv4 asignada', '10.10.0.100', 'class="font-mono w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]"').replace('class="w-full', 'style="display:none" class="w-full')}
            ${f('mac',       'Dirección MAC',  'AA-BB-CC-DD-EE-FF', 'class="font-mono w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]"').replace('class="w-full', 'class="w-full')}`;

    // Reconstruyo ipv4 y mac correctamente sin el hack de replace
    html = `
        <div class="grid grid-cols-2 gap-3">
            ${f('nombre_equipo', 'Nombre del equipo', 'IMJUVE-LAP-001')}
            ${f('cpu_marca',  'Marca CPU',    'Dell / HP / Lenovo')}
            ${f('cpu_modelo', 'Modelo CPU',   'Latitude 5540')}
            ${f('cpu_serie',  'No. Serie CPU')}
            <div>
                <label class="block text-xs font-bold text-[#544246] mb-1">IPv4 asignada</label>
                <input type="text" name="equipos[${idx}][ipv4]" placeholder="10.10.0.100"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-[#621132]">
            </div>
            <div>
                <label class="block text-xs font-bold text-[#544246] mb-1">Dirección MAC</label>
                <input type="text" name="equipos[${idx}][mac]" placeholder="AA-BB-CC-DD-EE-FF"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-[#621132]">
            </div>`;

    if (tipo === 'Laptop') {
        html += `
            ${f('cargador_serie',  'No. Serie Cargador')}
            ${f('docking_marca',   'Marca Docking')}
            ${f('docking_modelo',  'Modelo Docking')}
            ${f('docking_serie',   'No. Serie Docking')}
            ${f('candado',         'No. Candado')}`;
    }

    if (tipo === 'PC Avanzada' || tipo === 'PC Especializada') {
        html += `
            ${f('teclado_serie',   'No. Serie Teclado')}
            ${f('mouse_serie',     'No. Serie Mouse')}
            ${f('monitor_marca',   'Marca Monitor')}
            ${f('monitor_modelo',  'Modelo Monitor')}
            ${f('monitor_serie',   'No. Serie Monitor')}
            ${f('nobreak_marca',   'Marca No-Break')}
            ${f('nobreak_modelo',  'Modelo No-Break')}`;
    }

    if (tipo === 'PC Especializada') {
        html += `
            ${f('nobreak_serie',   'No. Serie No-Break')}
            <div>
                <label class="block text-xs font-bold text-[#544246] mb-1">IPv4 actual (asignada en red)</label>
                <input type="text" name="equipos[${idx}][ipv4_actual]" placeholder="10.10.0.101"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-[#621132]">
            </div>
            ${f('check_entrega',   'No. Check / Entrega')}`;
    }

    html += `
            <div class="col-span-2">
                <label class="block text-xs font-bold text-[#544246] mb-1">Observaciones</label>
                <input type="text" name="equipos[${idx}][observaciones]"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
            </div>
        </div>`;

    return html;
}

function agregarEquipo() {
    const idx = equipoIdx++;
    const div = document.createElement('div');
    div.className = 'equipo-item border border-[#E5E7EB] rounded-xl p-4 relative bg-[#fbf9f8]';
    div.dataset.idx = idx;
    div.innerHTML = `
        <button type="button" onclick="this.closest('.equipo-item').remove()"
            class="absolute top-2 right-2 text-[#544246] hover:text-red-600 transition-colors">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
        <div class="mb-3">
            <label class="block text-xs font-bold text-[#544246] mb-1">
                Tipo de equipo <span class="text-red-500">*</span>
            </label>
            <select name="equipos[${idx}][tipo]" required
                onchange="actualizarCamposEquipo(this)"
                class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                <option value="">— Selecciona tipo —</option>
                <option value="Laptop">Laptop</option>
                <option value="PC Avanzada">PC Avanzada</option>
                <option value="PC Especializada">PC Especializada</option>
            </select>
        </div>
        <div class="campos-equipo text-sm text-[#544246] italic">
            Selecciona un tipo para ver los campos correspondientes.
        </div>`;
    document.getElementById('equipos-container').appendChild(div);
}

function actualizarCamposEquipo(select) {
    const tipo     = select.value;
    const item     = select.closest('.equipo-item');
    const idx      = item.dataset.idx;
    const campos   = item.querySelector('.campos-equipo');
    if (!tipo) {
        campos.innerHTML = '<p class="text-sm text-[#544246] italic">Selecciona un tipo para ver los campos correspondientes.</p>';
        return;
    }
    campos.innerHTML = camposEquipo(idx, tipo);
}

document.getElementById('form-nuevo-usuario')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn  = document.getElementById('btn-guardar-usuario');
    const orig = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Guardando…';

    const form = new FormData(e.target);
    const body = Object.fromEntries(form);

    // Armar array de equipos desde los campos dinámicos
    const equipoEls = document.querySelectorAll('.equipo-item');
    const equipos   = [];
    equipoEls.forEach(el => {
        const idx = el.dataset.idx;
        const eq  = {};
        el.querySelectorAll('[name]').forEach(inp => {
            const key = inp.name.replace(`equipos[${idx}][`, '').replace(']', '');
            eq[key] = inp.value;
        });
        if (eq.tipo) equipos.push(eq);
    });

    const payload = {};
    for (const [k, v] of form.entries()) {
        if (!k.startsWith('equipos[')) payload[k] = v;
    }
    if (equipos.length) payload.equipos = equipos;

    try {
        const r = await fetch('{{ route("crm.empleados.store") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            },
            body: JSON.stringify(payload),
        });
        const data = await r.json();
        if (r.ok && data.ok) {
            closeNuevoModal();
            window.location.reload();
        } else {
            const msgs = data.errors
                ? Object.values(data.errors).flat().join('\n')
                : (data.message || 'Error al guardar.');
            alert(msgs);
        }
    } catch (err) {
        alert('Error de conexión. Intenta de nuevo.');
    } finally {
        btn.disabled = false;
        btn.textContent = orig;
    }
});
</script>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL: Nuevo Usuario                                                   --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div id="modal-nuevo" class="hidden fixed inset-0 z-[80] items-center justify-center bg-black/30 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-[#E5E7EB] shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-[#621132]/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[#621132] text-lg">person_add</span>
                </div>
                <h3 class="font-bold text-[#1b1c1c]">Nuevo Usuario</h3>
            </div>
            <button onclick="closeNuevoModal()" class="p-1.5 hover:bg-[#F3F4F6] rounded-full transition-colors text-[#544246]">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        {{-- Form (scrollable) --}}
        <form id="form-nuevo-usuario" class="overflow-y-auto flex-1 px-6 py-5 space-y-5">

            {{-- Datos personales --}}
            <section>
                <p class="text-xs font-bold uppercase tracking-wider text-[#621132] mb-3">Datos personales</p>
                <div class="grid grid-cols-3 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-bold text-[#544246] mb-1">Nombre(s) <span class="text-red-500">*</span></label>
                        <input type="text" name="nombre" required placeholder="Ana"
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#544246] mb-1">Apellido Paterno <span class="text-red-500">*</span></label>
                        <input type="text" name="apellido_paterno" required placeholder="García"
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#544246] mb-1">Apellido Materno</label>
                        <input type="text" name="apellido_materno" placeholder="López"
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-[#544246] mb-1">Puesto</label>
                        <input type="text" name="puesto" placeholder="Analista de sistemas"
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#544246] mb-1">Correo institucional</label>
                        <input type="email" name="correo" placeholder="ana.garcia@imjuve.gob.mx"
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-xs font-bold text-[#544246] mb-1">Departamento</label>
                    <select name="id_departamento"
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                        <option value="">— Sin asignar —</option>
                        @foreach($departamentos as $d)
                            <option value="{{ $d->id_departamento }}">{{ $d->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </section>

            {{-- Teléfono (colapsable) --}}
            <details class="border border-[#E5E7EB] rounded-xl overflow-hidden">
                <summary class="flex items-center gap-2 px-4 py-3 cursor-pointer select-none font-bold text-sm text-[#1b1c1c] hover:bg-[#fbf9f8] transition-colors list-none">
                    <span class="material-symbols-outlined text-[#544246] text-lg">phone</span>
                    Teléfono
                    <span class="text-xs font-normal text-[#544246] ml-1">(opcional)</span>
                </summary>
                <div class="px-4 pb-4 pt-1 grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-[#544246] mb-1">Número general</label>
                        <input type="text" name="tel_numero" placeholder="55 5066 3300"
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-[#544246] mb-1">Extensión</label>
                        <input type="number" name="tel_extension" placeholder="1234" min="1" max="9999"
                            class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                    </div>
                </div>
            </details>

            {{-- Equipos de cómputo (colapsable) --}}
            <details class="border border-[#E5E7EB] rounded-xl overflow-hidden">
                <summary class="flex items-center gap-2 px-4 py-3 cursor-pointer select-none font-bold text-sm text-[#1b1c1c] hover:bg-[#fbf9f8] transition-colors list-none">
                    <span class="material-symbols-outlined text-[#544246] text-lg">computer</span>
                    Equipos de cómputo
                    <span class="text-xs font-normal text-[#544246] ml-1">(opcional, puede agregar varios)</span>
                </summary>
                <div class="px-4 pb-4 pt-1">
                    <div id="equipos-container" class="space-y-3 mb-3"></div>
                    <button type="button" onclick="agregarEquipo()"
                        class="w-full py-2 border-2 border-dashed border-[#D4C19C] text-[#621132] rounded-lg text-sm font-bold hover:bg-[#D4C19C]/10 transition-colors flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Agregar equipo
                    </button>
                </div>
            </details>

        </form>

        {{-- Footer --}}
        <div class="flex gap-3 px-6 py-4 border-t border-[#E5E7EB] shrink-0">
            <button type="button" onclick="closeNuevoModal()"
                class="flex-1 py-2.5 border border-[#E5E7EB] text-[#544246] font-bold rounded-lg hover:bg-[#F3F4F6] transition-colors text-sm">
                Cancelar
            </button>
            <button type="submit" form="form-nuevo-usuario" id="btn-guardar-usuario"
                class="flex-1 py-2.5 bg-[#621132] text-white font-bold rounded-lg hover:opacity-90 transition-opacity text-sm">
                Dar de Alta
            </button>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════════════════ --}}
{{-- MODAL: Editar Usuario                                                  --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div id="modal-edit" class="hidden fixed inset-0 z-[80] items-center justify-center bg-black/30 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg flex flex-col max-h-[90vh]">

        <div class="flex items-center justify-between px-6 py-4 border-b border-[#E5E7EB] shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-[#621132]/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[#621132] text-lg">manage_accounts</span>
                </div>
                <h3 class="font-bold text-[#1b1c1c]">Editar Usuario</h3>
            </div>
            <button onclick="closeEditModal()" class="p-1.5 hover:bg-[#F3F4F6] rounded-full transition-colors text-[#544246]">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        <form id="form-edit-usuario" class="overflow-y-auto flex-1 px-6 py-5 space-y-4">
            <input type="hidden" id="edit-id">

            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold text-[#544246] mb-1">Nombre(s) <span class="text-red-500">*</span></label>
                    <input type="text" id="edit-nombre" name="nombre" required
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#544246] mb-1">Ap. Paterno <span class="text-red-500">*</span></label>
                    <input type="text" id="edit-ap" name="apellido_paterno" required
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#544246] mb-1">Ap. Materno</label>
                    <input type="text" id="edit-am" name="apellido_materno"
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold text-[#544246] mb-1">Puesto</label>
                    <input type="text" id="edit-puesto" name="puesto"
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                </div>
                <div>
                    <label class="block text-xs font-bold text-[#544246] mb-1">Correo institucional</label>
                    <input type="email" id="edit-correo" name="correo"
                        class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold text-[#544246] mb-1">Departamento</label>
                <select id="edit-depa" name="id_departamento"
                    class="w-full border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-[#621132]">
                    <option value="">— Sin asignar —</option>
                    @foreach($departamentos as $d)
                    <option value="{{ $d->id_departamento }}">{{ $d->nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Separador estado --}}
            <div class="border-t border-[#E5E7EB] pt-4">
                <p class="text-xs font-bold uppercase tracking-wider text-[#544246] mb-2">Estado del usuario</p>
                <button type="button" id="btn-baja-toggle" class="w-full py-2.5 border border-[#991B1B] text-[#991B1B] font-bold rounded-lg hover:bg-red-50 transition-colors text-sm">
                    Dar de Baja
                </button>
            </div>
        </form>

        <div class="flex gap-3 px-6 py-4 border-t border-[#E5E7EB] shrink-0">
            <button type="button" onclick="closeEditModal()"
                class="flex-1 py-2.5 border border-[#E5E7EB] text-[#544246] font-bold rounded-lg hover:bg-[#F3F4F6] transition-colors text-sm">
                Cancelar
            </button>
            <button type="submit" form="form-edit-usuario" id="btn-guardar-edit"
                class="flex-1 py-2.5 bg-[#621132] text-white font-bold rounded-lg hover:opacity-90 transition-opacity text-sm">
                Guardar cambios
            </button>
        </div>
    </div>
</div>
</x-layouts.app>
