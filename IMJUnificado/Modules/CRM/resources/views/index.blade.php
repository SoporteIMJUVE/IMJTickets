<x-layouts.app title="Usuarios — IMJUVE CRM">
<div class="p-8 relative" id="crm-page">

    {{-- Header --}}
    {{-- Header + toggle de vista --}}
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="text-[32px] font-bold leading-10 tracking-tight text-brand">Gestión de Usuarios</h2>
            <p class="text-muted text-sm mt-1">Directorio institucional y asignación de activos.</p>
        </div>
        <div class="flex items-center gap-3">
            <button onclick="alert('Ajustes del módulo aún no disponibles.')"
                    class="p-2 border border-border rounded-lg hover:bg-wash transition-colors text-muted"
                    title="Ajustes">
                <span class="material-symbols-outlined text-sm">settings</span>
            </button>
        </div>
    </div>

    {{-- KPI Bento --}}
    <div class="grid grid-cols-12 gap-4 mb-8">
        <div class="col-span-3 bg-canvas border border-border p-6 rounded-xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-status-active/10 text-status-active flex items-center justify-center">
                <span class="material-symbols-outlined">group</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Usuarios Activos</p>
                <p class="text-2xl font-bold leading-tight">{{ $totalActivos }}</p>
            </div>
        </div>
        <div class="col-span-3 bg-canvas border border-border p-6 rounded-xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-status-free/10 text-status-free flex items-center justify-center">
                <span class="material-symbols-outlined">computer</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Equipos Asignados</p>
                <p class="text-2xl font-bold leading-tight">{{ $totalEquipos }}</p>
            </div>
        </div>
        <div class="col-span-3 bg-canvas border border-border p-6 rounded-xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-gold/30 text-brand flex items-center justify-center">
                <span class="material-symbols-outlined">apartment</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Departamentos</p>
                <p class="text-2xl font-bold leading-tight">{{ $totalDeptos }}</p>
            </div>
        </div>
        <div class="col-span-3 bg-canvas border border-border p-6 rounded-xl flex items-center gap-4">
            <div class="w-12 h-12 rounded-full bg-status-critical/10 text-status-critical flex items-center justify-center">
                <span class="material-symbols-outlined">person_off</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Bajas</p>
                <p class="text-2xl font-bold leading-tight">{{ $totalBajas }}</p>
            </div>
        </div>
    </div>

    {{-- User Table --}}
    <div class="bg-canvas border border-border rounded-xl overflow-hidden shadow-sm">

        {{-- Encabezado con filtros --}}
        <x-tabla-encabezado titulo="Directorio de Empleados" tab="crm" exportUrl="{{ route('crm.exportar') }}">
            <x-slot:acciones>
                    <button onclick="openNuevoModal()" class="px-4 py-2 bg-brand text-white rounded flex items-center gap-2 hover:opacity-90 transition-opacity text-sm font-bold">
                        <span class="material-symbols-outlined text-sm">person_add</span>
                        Nuevo Usuario
                    </button>
            </x-slot:acciones>
            <x-slot:filtros>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Buscar</label>
                    <div class="relative">
                        <span class="material-symbols-outlined absolute left-2 top-1/2 -translate-y-1/2 text-muted text-sm">search</span>
                        <input class="pl-7 pr-3 py-1.5 text-sm border border-border rounded bg-canvas outline-none focus:ring-2 focus:ring-brand w-52"
                               placeholder="Nombre, puesto..." type="text" id="crm-search" oninput="aplicarFiltros()">
                    </div>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Departamento</label>
                    <select id="filter-depa" onchange="aplicarFiltros()"
                            class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand">
                        <option value="">Todos</option>
                        @foreach($departamentos as $d)
                        <option value="{{ strtolower($d->nombre) }}">{{ $d->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Estado</label>
                    <select id="filter-estado" onchange="aplicarFiltros()"
                            class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand">
                        <option value="">Todos</option>
                        <option value="1">Activos</option>
                        <option value="0">Bajas</option>
                    </select>
                </div>
            </x-slot:filtros>
        </x-tabla-encabezado>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse" id="users-table">
                <thead class="bg-wash border-b border-border">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted">Usuario</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted">Área / Dirección</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted">Equipo</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted">Extensión</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted">Estado</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border" id="users-tbody">
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
                        ) . '@imjuventud.gob.mx';
                    @endphp
                    <tr class="hover:bg-gold/5 transition-colors cursor-pointer group user-row"
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
                                <div class="w-8 h-8 rounded-full bg-gold flex items-center justify-center text-brand font-bold text-xs flex-shrink-0">
                                    {{ $iniciales }}
                                </div>
                                <div>
                                    <p class="font-bold text-sm text-ink">{{ $nombreCompleto }}</p>
                                    <p class="text-[11px] text-muted">{{ $correo }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-muted">{{ $emp->departamento_nombre ?? '—' }}</td>
                        <td class="px-6 py-4 text-sm">
                            @if($emp->total_equipos > 0)
                            <div class="flex items-center gap-1 text-muted">
                                <span class="material-symbols-outlined text-sm">laptop</span>
                                {{ $emp->total_equipos }} equipo{{ $emp->total_equipos > 1 ? 's' : '' }}
                            </div>
                            @else
                            <span class="text-muted">Sin asignar</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 font-mono text-sm text-brand">
                            {{ $emp->extension ? 'ext. ' . $emp->extension : '—' }}
                        </td>
                        <td class="px-6 py-4">
                            @if($emp->activo)
                            <span class="bg-status-active/10 text-status-active px-3 py-1 rounded-full text-[11px] font-bold uppercase">Activo</span>
                            @else
                            <span class="bg-status-critical/10 text-status-critical px-3 py-1 rounded-full text-[11px] font-bold uppercase">Baja</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button class="p-2 hover:bg-wash rounded-full text-muted group-hover:text-brand transition-colors">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-muted text-sm">
                            No hay empleados registrados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="px-6 py-4 border-t border-border flex justify-between items-center bg-surface">
            <p class="text-sm text-muted" id="tabla-conteo">{{ $empleados->count() }} registro(s)</p>
            <div class="flex gap-2">
                <button class="w-8 h-8 flex items-center justify-center rounded border border-border hover:bg-wash">
                    <span class="material-symbols-outlined text-sm">chevron_left</span>
                </button>
                <button class="w-8 h-8 flex items-center justify-center rounded bg-brand text-white font-bold text-sm">1</button>
                <button class="w-8 h-8 flex items-center justify-center rounded border border-border hover:bg-wash font-bold text-sm">2</button>
                <button class="w-8 h-8 flex items-center justify-center rounded border border-border hover:bg-wash">
                    <span class="material-symbols-outlined text-sm">chevron_right</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Side Panel: User Detail (400px) --}}
<div class="fixed top-0 right-0 h-screen w-[400px] bg-canvas shadow-2xl border-l border-gold z-[60] detail-panel closed flex flex-col" id="user-panel">
    {{-- Header --}}
    <div class="p-6 border-b border-border bg-surface flex justify-between items-start">
        <div>
            <div class="w-16 h-16 rounded-full bg-gold flex items-center justify-center text-brand font-bold text-xl mb-3" id="panel-avatar">US</div>
            <div class="flex items-center gap-2 mb-1">
                <h3 class="text-xl font-bold" id="panel-name">Usuario</h3>
                <span id="panel-estado-badge" class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full"></span>
            </div>
            <p class="text-sm text-muted" id="panel-area">Área</p>
        </div>
        <button class="p-2 hover:bg-wash rounded-full transition-colors" onclick="closeUserPanel()">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    {{-- Tabs --}}
    <div class="flex border-b border-border px-6 bg-canvas">
        <button class="px-4 py-3 text-brand font-bold border-b-2 border-brand text-sm" onclick="switchPanelTab('recursos', this)">Recursos</button>
        <button class="px-4 py-3 text-muted font-medium text-sm hover:text-brand" onclick="switchPanelTab('historial', this)">Historial</button>
        <button class="px-4 py-3 text-muted font-medium text-sm hover:text-brand" onclick="switchPanelTab('tickets', this)">Tickets</button>
    </div>

    {{-- Scrollable Body --}}
    <div class="flex-1 overflow-y-auto custom-scrollbar">
        {{-- Tab: Recursos --}}
        <div class="p-6 space-y-6" id="tab-recursos">
            <section>
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-muted mb-4 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">badge</span>
                    INFORMACIÓN DE CONTACTO
                </h4>
                <div class="grid grid-cols-2 gap-4 bg-wash p-4 rounded-lg">
                    <div>
                        <p class="text-[10px] text-muted font-bold uppercase">Correo</p>
                        <p class="text-sm font-semibold break-all" id="panel-correo">—</p>
                    </div>
                    <div>
                        <p class="text-[10px] text-muted font-bold uppercase">Extensión</p>
                        <p class="text-sm font-semibold" id="panel-ext">—</p>
                    </div>
                    <div class="col-span-2">
                        <p class="text-[10px] text-muted font-bold uppercase">Departamento</p>
                        <p class="text-sm font-semibold" id="panel-depa">—</p>
                    </div>
                </div>
            </section>

            <section>
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-muted mb-3 flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">computer</span>
                    EQUIPOS ASIGNADOS
                    <span class="ml-auto font-mono text-brand" id="panel-eq-count"></span>
                </h4>
                <div id="panel-equipos-list">
                    <div class="flex items-center justify-center py-8 text-muted text-xs">
                        <span class="material-symbols-outlined text-2xl animate-spin mr-2">progress_activity</span>
                        Cargando…
                    </div>
                </div>
            </section>
        </div>

        {{-- Tab: Historial — todos los tickets del empleado --}}
        <div class="p-6 hidden" id="tab-historial">
            <div id="historial-content">
                <div class="text-center text-muted py-12">
                    <span class="material-symbols-outlined text-4xl mb-2 block">history</span>
                    <p class="text-sm">Sin historial de tickets</p>
                </div>
            </div>
        </div>

        {{-- Tab: Tickets — solo tickets activos (Abierto / Atendiendo) --}}
        <div class="p-6 hidden" id="tab-tickets">
            <div id="tickets-activos-content">
                <div class="text-center text-muted py-12">
                    <span class="material-symbols-outlined text-4xl mb-2 block">confirmation_number</span>
                    <p class="text-sm">Sin tickets activos</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Footer Actions --}}
    <div class="p-6 border-t border-border bg-surface grid grid-cols-2 gap-3">
        <button onclick="openEditModal()" class="w-full py-3 bg-wash text-brand font-bold rounded-lg hover:bg-surface-high transition-colors flex items-center justify-center gap-2 text-sm">
            <span class="material-symbols-outlined text-sm">edit</span>
            Editar
        </button>
        <button class="w-full py-3 bg-brand text-white font-bold rounded-lg hover:opacity-90 transition-opacity flex items-center justify-center gap-2 text-sm">
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
let currentEquipos  = [];

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
        badge.style.cssText = 'background-color:rgba(22,101,52,.1);color:var(--color-status-active)';
    } else {
        badge.textContent = 'Baja';
        badge.style.cssText = 'background-color:rgba(153,27,27,.1);color:var(--color-status-critical)';
    }

    // Tab Recursos — datos de contacto
    document.getElementById('panel-correo').innerText = correo || '—';
    document.getElementById('panel-ext').innerText    = ext ? 'ext. ' + ext : '—';
    document.getElementById('panel-depa').innerText   = depa || '—';

    // Equipos: spinner mientras carga
    document.getElementById('panel-equipos-list').innerHTML =
        '<div class="flex items-center py-6 text-muted text-xs gap-2"><span class="material-symbols-outlined text-xl animate-spin">progress_activity</span>Cargando equipos…</div>';
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
        currentEquipos = equipos;
        renderEquiposPanel(equipos);
    } catch (e) {
        currentEquipos = [];
        document.getElementById('panel-equipos-list').innerHTML =
            '<p class="text-xs text-red-500">Error al cargar equipos.</p>';
    }
}

function renderEquiposPanel(equipos) {
    const list = document.getElementById('panel-equipos-list');
    const count = document.getElementById('panel-eq-count');

    if (!equipos.length) {
        count.textContent = '';
        list.innerHTML = `<div class="flex flex-col items-center py-8 text-muted">
            <span class="material-symbols-outlined text-3xl mb-2 opacity-40">computer_off</span>
            <p class="text-xs">Sin equipos asignados</p>
        </div>`;
        return;
    }

    count.textContent = equipos.length + ' equipo' + (equipos.length > 1 ? 's' : '');

    const tipoIcon  = { 'Laptop':'laptop', 'PC Avanzada':'computer', 'PC Especializada':'developer_board' };
    const tipoColor = { 'Laptop':'var(--color-status-free)', 'PC Avanzada':'var(--color-status-active)', 'PC Especializada':'var(--color-status-low)' };

    list.innerHTML = equipos.map(eq => {
        const icon  = tipoIcon[eq.tipo]  ?? 'computer';
        const color = tipoColor[eq.tipo] ?? 'var(--color-brand)';
        const marca = [eq.cpu_marca, eq.cpu_modelo].filter(Boolean).join(' ') || '—';
        const serie = eq.cpu_serie || '—';
        const ip    = eq.ipv4 || eq.ipv4_actual || null;
        const esFk  = eq.match === 'fk';

        // Badge de vinculación
        const matchBadge = esFk
            ? `<span title="Vinculado formalmente (FK)" style="background:#16653410;color:var(--color-status-active);font-size:9px;font-weight:700;padding:1px 6px;border-radius:999px;letter-spacing:.04em">● VINCULADO</span>`
            : `<span title="Coincidencia por nombre — vincula desde Kardex" style="background:#92400E18;color:var(--color-status-attend);font-size:9px;font-weight:700;padding:1px 6px;border-radius:999px;letter-spacing:.04em">⚠ POR NOMBRE</span>`;

        return `<div class="border rounded-lg p-3 mb-2 last:mb-0 transition-colors ${esFk ? 'border-status-active/30 hover:border-status-active/60' : 'border-border hover:border-gold'}">
            <div class="flex items-center gap-2 mb-2">
                <span class="material-symbols-outlined text-lg" style="color:${color}">${icon}</span>
                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full"
                      style="background:${color}1a;color:${color}">${escHtml(eq.tipo)}</span>
                ${matchBadge}
                ${eq.num_inventario ? `<span class="ml-auto text-[10px] font-mono text-muted shrink-0">Inv.&nbsp;${escHtml(String(eq.num_inventario))}</span>` : ''}
            </div>
            <p class="text-sm font-bold text-ink mb-2">${escHtml(marca)}</p>
            <div class="grid grid-cols-2 gap-x-4 gap-y-1">
                <div>
                    <p class="text-[10px] text-muted font-bold uppercase">No. Serie</p>
                    <p class="font-mono text-xs text-brand">${escHtml(serie)}</p>
                </div>
                <div>
                    <p class="text-[10px] text-muted font-bold uppercase">IPv4</p>
                    ${ip
                        ? `<p class="font-mono text-xs text-brand">${escHtml(ip)}</p>`
                        : `<p class="text-xs text-muted opacity-40">Sin IP</p>`
                    }
                </div>
                ${eq.mac ? `<div class="col-span-2 mt-1">
                    <p class="text-[10px] text-muted font-bold uppercase">MAC</p>
                    <p class="font-mono text-[11px] text-muted">${escHtml(eq.mac)}</p>
                </div>` : ''}
            </div>
        </div>`;
    }).join('');
}

function renderTicketItem(t) {
    const label = {0:'Abierto',1:'Atendiendo',2:'Cerrado'}[t.estado] ?? '—';
    const style = {
        0: 'background:var(--color-status-free-bg);color:var(--color-status-free)',
        1: 'background:var(--color-status-attend-bg);color:var(--color-status-attend)',
        2: 'background:var(--color-status-closed-bg);color:var(--color-status-active)',
    }[t.estado] ?? '';
    const fecha = t.fecha
        ? new Date(t.fecha).toLocaleDateString('es-MX', {day:'2-digit', month:'short', year:'numeric'})
        : '';
    return `<div class="border border-border rounded-lg p-4 mb-3 last:mb-0">
        <div class="flex justify-between items-center mb-2">
            <span style="color:var(--color-brand);background:var(--color-brand)15;padding:2px 8px;border-radius:4px;font-family:monospace;font-size:10px;font-weight:700">#${t.id}</span>
            <span style="${style};padding:2px 8px;border-radius:999px;font-size:10px;font-weight:700">${label}</span>
        </div>
        <p style="font-size:13px;font-weight:700;color:var(--color-ink);margin-bottom:4px">${escHtml(t.tipo)}</p>
        <p style="font-size:11px;color:var(--color-muted)">${escHtml(t.descripcion)}</p>
        <p style="font-size:10px;color:var(--color-muted);margin-top:6px;opacity:.7">${fecha}</p>
    </div>`;
}

function emptyState(icon, msg) {
    return `<div style="text-align:center;color:var(--color-muted);padding:48px 0">
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
        b.className = 'px-4 py-3 text-muted font-medium text-sm hover:text-brand';
    });
    document.getElementById('tab-' + tab).classList.remove('hidden');
    btn.className = 'px-4 py-3 text-brand font-bold border-b-2 border-brand text-sm';
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { closeUserPanel(); closeUsuarioModal(); }
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

// Los filtros usan onchange/oninput inline desde el componente x-tabla-encabezado

// ─── Modal Usuario (Nuevo / Editar) ─────────────────────────────────────────
let _modalMode = 'nuevo';

function openNuevoModal() {
    _modalMode = 'nuevo';
    document.getElementById('form-usuario').reset();
    document.getElementById('equipos-container').innerHTML = '';
    equipoIdx = 0;
    document.getElementById('usr-id').value = '';
    document.getElementById('modal-usr-titulo').textContent    = 'Nuevo Usuario';
    document.getElementById('modal-usr-icon').textContent      = 'person_add';
    document.getElementById('btn-guardar-usuario').textContent = 'Dar de Alta';
    document.getElementById('section-baja').classList.add('hidden');
    document.getElementById('section-extras-tel').classList.remove('hidden');
    document.getElementById('section-extras-eq').classList.remove('hidden');
    document.getElementById('modal-usuario').classList.remove('hidden');
    document.getElementById('modal-usuario').classList.add('flex');
}

function openEditModal() {
    if (!currentEmpleado.id) return;
    _modalMode = 'editar';
    const e = currentEmpleado;

    document.getElementById('usr-id').value     = e.id;
    document.getElementById('usr-nombre').value = e.nombreRaw || '';
    document.getElementById('usr-ap').value     = e.ap || '';
    document.getElementById('usr-am').value     = e.am || '';
    document.getElementById('usr-puesto').value = e.puesto || '';
    document.getElementById('usr-correo').value = e.correo || '';

    const sel = document.getElementById('usr-depa');
    for (const opt of sel.options) opt.selected = opt.value === String(e.depaId);

    const btnBaja = document.getElementById('btn-baja-toggle');
    if (e.activo) {
        btnBaja.textContent = 'Dar de Baja';
        btnBaja.className   = 'w-full py-2.5 border border-status-critical text-status-critical font-bold rounded-lg hover:bg-red-50 transition-colors text-sm';
    } else {
        btnBaja.textContent = 'Reactivar Usuario';
        btnBaja.className   = 'w-full py-2.5 border border-status-active text-status-active font-bold rounded-lg hover:bg-green-50 transition-colors text-sm';
    }

    document.getElementById('modal-usr-titulo').textContent    = 'Editar Usuario';
    document.getElementById('modal-usr-icon').textContent      = 'manage_accounts';
    document.getElementById('btn-guardar-usuario').textContent = 'Guardar cambios';
    document.getElementById('section-baja').classList.remove('hidden');
    document.getElementById('section-extras-tel').classList.add('hidden');

    // Poblar equipos existentes en el formulario
    const container = document.getElementById('equipos-container');
    container.innerHTML = '';
    equipoIdx = 0;
    currentEquipos.forEach(eq => {
        container.appendChild(crearEquipoExistente(equipoIdx++, eq));
    });
    const sectionEq = document.getElementById('section-extras-eq');
    sectionEq.classList.remove('hidden');
    if (currentEquipos.length > 0) sectionEq.open = true;

    document.getElementById('modal-usuario').classList.remove('hidden');
    document.getElementById('modal-usuario').classList.add('flex');
}

function closeUsuarioModal() {
    document.getElementById('modal-usuario').classList.add('hidden');
    document.getElementById('modal-usuario').classList.remove('flex');
    document.getElementById('form-usuario').reset();
    document.getElementById('equipos-container').innerHTML = '';
    equipoIdx = 0;
}

document.addEventListener('DOMContentLoaded', () => {
    const CSRF = '{{ csrf_token() }}';

    // ── Submit unificado ──────────────────────────────────────────────────────
    document.getElementById('form-usuario')?.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        const btn  = document.getElementById('btn-guardar-usuario');
        const orig = btn.textContent;
        btn.disabled = true; btn.textContent = 'Guardando…';

        try {
            if (_modalMode === 'nuevo') {
                const form      = new FormData(ev.target);
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

                const r = await fetch('{{ route("crm.empleados.store") }}', {
                    method: 'POST',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF,
                               'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' },
                    body: JSON.stringify(payload),
                });
                const data = await r.json();
                if (r.ok && data.ok) { closeUsuarioModal(); window.location.reload(); }
                else {
                    const msgs = data.errors ? Object.values(data.errors).flat().join('\n') : (data.message || 'Error al guardar.');
                    alert(msgs);
                }
            } else {
                const id        = document.getElementById('usr-id').value;
                const equipoEls = document.querySelectorAll('.equipo-item');
                const equipos   = [];
                equipoEls.forEach(el => {
                    const i  = el.dataset.idx;
                    const eq = {};
                    el.querySelectorAll('[name]').forEach(inp => {
                        const key = inp.name.replace(`equipos[${i}][`, '').replace(']', '');
                        eq[key] = inp.value;
                    });
                    if (eq.tipo) equipos.push(eq);
                });
                const payload = {
                    nombre:           document.getElementById('usr-nombre').value,
                    apellido_paterno: document.getElementById('usr-ap').value,
                    apellido_materno: document.getElementById('usr-am').value,
                    puesto:           document.getElementById('usr-puesto').value,
                    correo:           document.getElementById('usr-correo').value,
                    id_departamento:  document.getElementById('usr-depa').value || null,
                };
                if (equipos.length) payload.equipos = equipos;
                const r = await fetch(`/crm/empleados/${id}`, {
                    method: 'PATCH',
                    headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF,
                               'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' },
                    body: JSON.stringify(payload),
                });
                const data = await r.json();
                if (r.ok && data.ok) { closeUsuarioModal(); window.location.reload(); }
                else {
                    const msgs = data.errors ? Object.values(data.errors).flat().join('\n') : (data.message || 'Error.');
                    alert(msgs);
                }
            }
        } catch (err) { alert('Error de conexión.'); }
        finally { btn.disabled = false; btn.textContent = orig; }
    });

    // ── Baja / Reactivar ─────────────────────────────────────────────────────
    document.getElementById('btn-baja-toggle')?.addEventListener('click', async () => {
        const id     = document.getElementById('usr-id').value;
        const activo = currentEmpleado.activo;
        const url    = activo ? `/crm/empleados/${id}` : `/crm/empleados/${id}/reactivar`;
        const method = activo ? 'DELETE' : 'PATCH';
        if (!confirm(activo ? '¿Confirmas dar de baja a este usuario?' : '¿Reactivar este usuario?')) return;

        const r = await fetch(url, {
            method, headers: { 'X-CSRF-TOKEN':CSRF, 'X-Requested-With':'XMLHttpRequest', 'Accept':'application/json' },
        });
        if ((await r.json()).ok) { closeUsuarioModal(); window.location.reload(); }
    });
});

let equipoIdx = 0;

function camposEquipo(idx, tipo, vals = {}) {
    const v = name => (vals[name] != null && vals[name] !== '') ? `value="${escHtml(String(vals[name]))}"` : '';
    const f = (name, label, placeholder = '') =>
        `<div>
            <label class="block text-xs font-bold text-muted mb-1">${label}</label>
            <input type="text" name="equipos[${idx}][${name}]" placeholder="${placeholder}" ${v(name)}
                class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
        </div>`;

    // Campos comunes a todos los tipos
    let html = `
        <div class="grid grid-cols-2 gap-3">
            ${f('nombre_equipo', 'Nombre del equipo', 'IMJUVE-LAP-001')}
            <div>
                <label class="block text-xs font-bold text-muted mb-1">Marca CPU</label>
                <input type="text" name="equipos[${idx}][cpu_marca]" placeholder="Dell / HP / Lenovo"
                    class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
            </div>
            ${f('cpu_modelo', 'Modelo CPU', 'Latitude 5540')}
            ${f('cpu_serie', 'No. Serie CPU')}
            ${f('ipv4',      'IPv4 asignada', '10.10.0.100', 'class="font-mono w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand"').replace('class="w-full', 'style="display:none" class="w-full')}
            ${f('mac',       'Dirección MAC',  'AA-BB-CC-DD-EE-FF', 'class="font-mono w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand"').replace('class="w-full', 'class="w-full')}`;

    // Reconstruyo ipv4 y mac correctamente sin el hack de replace
    html = `
        <div class="grid grid-cols-2 gap-3">
            ${f('nombre_equipo', 'Nombre del equipo', 'IMJUVE-LAP-001')}
            ${f('cpu_marca',  'Marca CPU',    'Dell / HP / Lenovo')}
            ${f('cpu_modelo', 'Modelo CPU',   'Latitude 5540')}
            ${f('cpu_serie',  'No. Serie CPU')}
            <div>
                <label class="block text-xs font-bold text-muted mb-1">IPv4 asignada</label>
                <input type="text" name="equipos[${idx}][ipv4]" placeholder="10.10.0.100" ${v('ipv4')}
                    class="w-full border border-border rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-brand">
            </div>
            <div>
                <label class="block text-xs font-bold text-muted mb-1">Dirección MAC</label>
                <input type="text" name="equipos[${idx}][mac]" placeholder="AA-BB-CC-DD-EE-FF" ${v('mac')}
                    class="w-full border border-border rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-brand">
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
                <label class="block text-xs font-bold text-muted mb-1">IPv4 actual (asignada en red)</label>
                <input type="text" name="equipos[${idx}][ipv4_actual]" placeholder="10.10.0.101" ${v('ipv4_actual')}
                    class="w-full border border-border rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:border-brand">
            </div>
            ${f('check_entrega',   'No. Check / Entrega')}`;
    }

    html += `
            <div class="col-span-2">
                <label class="block text-xs font-bold text-muted mb-1">Observaciones</label>
                <input type="text" name="equipos[${idx}][observaciones]"
                    class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
            </div>
        </div>`;

    return html;
}

function agregarEquipo() {
    const idx = equipoIdx++;
    const div = document.createElement('div');
    div.className = 'equipo-item border border-border rounded-xl p-4 relative bg-surface';
    div.dataset.idx = idx;
    div.innerHTML = `
        <button type="button" onclick="this.closest('.equipo-item').remove()"
            class="absolute top-2 right-2 text-muted hover:text-red-600 transition-colors">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
        <div class="mb-3">
            <label class="block text-xs font-bold text-muted mb-1">
                Tipo de equipo <span class="text-red-500">*</span>
            </label>
            <select name="equipos[${idx}][tipo]" required
                onchange="actualizarCamposEquipo(this)"
                class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
                <option value="">— Selecciona tipo —</option>
                <option value="Laptop">Laptop</option>
                <option value="PC Avanzada">PC Avanzada</option>
                <option value="PC Especializada">PC Especializada</option>
            </select>
        </div>
        <div class="campos-equipo text-sm text-muted italic">
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
        campos.innerHTML = '<p class="text-sm text-muted italic">Selecciona un tipo para ver los campos correspondientes.</p>';
        return;
    }
    campos.innerHTML = camposEquipo(idx, tipo);
}

function crearEquipoExistente(idx, eq) {
    const tipoColor = { 'Laptop':'var(--color-status-free)', 'PC Avanzada':'var(--color-status-active)', 'PC Especializada':'var(--color-status-low)' };
    const color = tipoColor[eq.tipo] ?? 'var(--color-brand)';
    const div = document.createElement('div');
    div.className = 'equipo-item border border-gold rounded-xl p-4 relative bg-surface';
    div.dataset.idx = idx;
    div.innerHTML = `
        <button type="button" onclick="this.closest('.equipo-item').remove()"
            class="absolute top-2 right-2 text-muted hover:text-red-600 transition-colors">
            <span class="material-symbols-outlined text-lg">close</span>
        </button>
        <input type="hidden" name="equipos[${idx}][id]" value="${escHtml(String(eq.id))}">
        <input type="hidden" name="equipos[${idx}][tipo]" value="${escHtml(eq.tipo)}">
        <div class="mb-3 flex items-center gap-2">
            <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded-full"
                  style="background:${color}1a;color:${color}">${escHtml(eq.tipo)}</span>
            <span class="text-[10px] text-muted">Equipo existente</span>
        </div>
        <div class="campos-equipo text-sm">
            ${camposEquipo(idx, eq.tipo, eq)}
        </div>`;
    return div;
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
{{-- MODAL: Usuario (Nuevo / Editar — modal unificado)                      --}}
{{-- ═══════════════════════════════════════════════════════════════════════ --}}
<div id="modal-usuario" class="hidden fixed inset-0 z-[80] items-center justify-center bg-black/30 backdrop-blur-sm p-4">
    <div class="bg-canvas rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] flex flex-col">

        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-border shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-brand/10 flex items-center justify-center">
                    <span class="material-symbols-outlined text-brand text-lg" id="modal-usr-icon">person_add</span>
                </div>
                <h3 class="font-bold text-ink" id="modal-usr-titulo">Nuevo Usuario</h3>
            </div>
            <button onclick="closeUsuarioModal()" class="p-1.5 hover:bg-wash rounded-full transition-colors text-muted">
                <span class="material-symbols-outlined text-lg">close</span>
            </button>
        </div>

        {{-- Form (scrollable) --}}
        <form id="form-usuario" class="overflow-y-auto flex-1 px-6 py-5 space-y-5">
            <input type="hidden" id="usr-id">

            {{-- Datos personales --}}
            <section>
                <p class="text-xs font-bold uppercase tracking-wider text-brand mb-3">Datos personales</p>
                <div class="grid grid-cols-3 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-bold text-muted mb-1">Nombre(s) <span class="text-red-500">*</span></label>
                        <input type="text" id="usr-nombre" name="nombre" required placeholder="Ana"
                            class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted mb-1">Ap. Paterno <span class="text-red-500">*</span></label>
                        <input type="text" id="usr-ap" name="apellido_paterno" required placeholder="García"
                            class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted mb-1">Ap. Materno</label>
                        <input type="text" id="usr-am" name="apellido_materno" placeholder="López"
                            class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-muted mb-1">Puesto</label>
                        <input type="text" id="usr-puesto" name="puesto" placeholder="Analista de sistemas"
                            class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted mb-1">Correo institucional</label>
                        <input type="email" id="usr-correo" name="correo" placeholder="ana.garcia@imjuventud.gob.mx"
                            class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
                    </div>
                </div>
                <div class="mt-3">
                    <label class="block text-xs font-bold text-muted mb-1">Departamento</label>
                    <select id="usr-depa" name="id_departamento"
                        class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
                        <option value="">— Sin asignar —</option>
                        @foreach($departamentos as $d)
                            <option value="{{ $d->id_departamento }}">{{ $d->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </section>

            {{-- Estado — solo visible en modo editar --}}
            <div id="section-baja" class="hidden border-t border-border pt-4">
                <p class="text-xs font-bold uppercase tracking-wider text-muted mb-2">Estado del usuario</p>
                <button type="button" id="btn-baja-toggle"
                    class="w-full py-2.5 border border-status-critical text-status-critical font-bold rounded-lg hover:bg-red-50 transition-colors text-sm">
                    Dar de Baja
                </button>
            </div>

            {{-- Teléfono — solo visible en modo nuevo --}}
            <details id="section-extras-tel" class="border border-border rounded-xl overflow-hidden">
                <summary class="flex items-center gap-2 px-4 py-3 cursor-pointer select-none font-bold text-sm text-ink hover:bg-surface transition-colors list-none">
                    <span class="material-symbols-outlined text-muted text-lg">phone</span>
                    Teléfono
                    <span class="text-xs font-normal text-muted ml-1">(opcional)</span>
                </summary>
                <div class="px-4 pb-4 pt-1 grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-muted mb-1">Número general</label>
                        <input type="text" name="tel_numero" placeholder="55 5066 3300"
                            class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-muted mb-1">Extensión</label>
                        <input type="number" name="tel_extension" placeholder="1234" min="1" max="9999"
                            class="w-full border border-border rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-brand">
                    </div>
                </div>
            </details>

            {{-- Equipos de cómputo — solo visible en modo nuevo --}}
            <details id="section-extras-eq" class="border border-border rounded-xl overflow-hidden">
                <summary class="flex items-center gap-2 px-4 py-3 cursor-pointer select-none font-bold text-sm text-ink hover:bg-surface transition-colors list-none">
                    <span class="material-symbols-outlined text-muted text-lg">computer</span>
                    Equipos de cómputo
                    <span class="text-xs font-normal text-muted ml-1">(opcional, puede agregar varios)</span>
                </summary>
                <div class="px-4 pb-4 pt-1">
                    <div id="equipos-container" class="space-y-3 mb-3"></div>
                    <button type="button" onclick="agregarEquipo()"
                        class="w-full py-2 border-2 border-dashed border-gold text-brand rounded-lg text-sm font-bold hover:bg-gold/10 transition-colors flex items-center justify-center gap-1">
                        <span class="material-symbols-outlined text-lg">add</span>
                        Agregar equipo
                    </button>
                </div>
            </details>

        </form>

        {{-- Footer --}}
        <div class="flex gap-3 px-6 py-4 border-t border-border shrink-0">
            <button type="button" onclick="closeUsuarioModal()"
                class="flex-1 py-2.5 border border-border text-muted font-bold rounded-lg hover:bg-wash transition-colors text-sm">
                Cancelar
            </button>
            <button type="submit" form="form-usuario" id="btn-guardar-usuario"
                class="flex-1 py-2.5 bg-brand text-white font-bold rounded-lg hover:opacity-90 transition-opacity text-sm">
                Dar de Alta
            </button>
        </div>
    </div>
</div>
</x-layouts.app>
