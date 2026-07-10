<x-layouts.app title="Kardex — IMJUVE CRM">
<div class="p-8" id="kardex-page">

    {{-- Header --}}
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="text-[32px] font-bold leading-10 tracking-tight text-brand">Kardex de Insumos y Resguardos</h2>
            <p class="text-muted text-sm mt-1">Control de inventario técnico y asignación institucional de recursos.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex gap-2 bg-[#efeded] rounded-lg p-1">
                <button id="tab-btn-equipos" onclick="switchKardexTab('equipos', this)"
                        class="px-6 py-2 rounded-md text-sm font-bold transition-all bg-canvas text-brand shadow-sm">
                    Equipos
                </button>
                <button id="tab-btn-insumos" onclick="switchKardexTab('insumos', this)"
                        class="px-6 py-2 rounded-md text-sm font-bold transition-all text-muted hover:bg-surface-high">
                    Insumos
                </button>
                <button id="tab-btn-resguardos" onclick="switchKardexTab('resguardos', this)"
                        class="px-6 py-2 rounded-md text-sm font-bold transition-all text-muted hover:bg-surface-high">
                    Resguardos
                </button>
            </div>
        </div>
    </div>

    {{-- ---- TAB: EQUIPOS (activo por defecto) ---- --}}
    <div id="tab-equipos">
        {{-- Stats --}}
        <div class="grid grid-cols-4 gap-4 mb-6">
            @php $equipStats = [
                ['label'=>'Total Equipos',    'value'=>$totalEquipos,  'icon'=>'computer',       'color'=>'var(--color-status-active)'],
                ['label'=>'En Almacén',        'value'=>$enAlmacen,     'icon'=>'inventory',      'color'=>'var(--color-status-free)'],
                ['label'=>'Mantenimiento',     'value'=>$mantenimiento, 'icon'=>'build',          'color'=>'var(--color-status-low)'],
                ['label'=>'Insumos Críticos',  'value'=>$criticos,      'icon'=>'report_problem', 'color'=>'var(--color-status-critical)'],
            ]; @endphp
            @foreach($equipStats as $s)
            <div class="bg-canvas border border-border p-5 rounded-xl flex items-center gap-4">
                <div class="w-11 h-11 rounded-full flex items-center justify-center shrink-0" style="background:{{ $s['color'] }}1a; color:{{ $s['color'] }}">
                    <span class="material-symbols-outlined">{{ $s['icon'] }}</span>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-muted">{{ $s['label'] }}</p>
                    <p class="text-2xl font-bold leading-tight">{{ $s['value'] }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Tabla Equipos --}}
        <div class="bg-canvas border border-border rounded-xl overflow-hidden shadow-sm">
            <x-tabla-encabezado titulo="Inventario de Equipos" tab="equipos" exportUrl="{{ route('kardex.exportar', 'equipos') }}">
                <x-slot:filtros>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Tipo</label>
                        <select id="f-eq-tipo" onchange="filtrarEquipos()"
                                class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand">
                            <option value="">Todos</option>
                            <option>Laptop</option>
                            <option>PC Avanzada</option>
                            <option>PC Especializada</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Estado</label>
                        <select id="f-eq-estado" onchange="filtrarEquipos()"
                                class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand">
                            <option value="">Todos</option>
                            <option>Almacén</option>
                            <option>Asignado</option>
                            <option>Mantenimiento</option>
                            <option>Baja</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Área / Responsable</label>
                        <input id="f-eq-texto" oninput="filtrarEquipos()" type="text" placeholder="Buscar..."
                               class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand w-52">
                    </div>
                </x-slot:filtros>
            </x-tabla-encabezado>

            <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-wash border-b border-border">
                    <tr>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">No. Inv</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Tipo</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Marca / Modelo</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">No. Serie</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Área</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Responsable</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Estado</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted text-right">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border" id="tbody-equipos">
                    @forelse($equipos as $eq)
                    @php
                        $estadoDisplay = match(true) {
                            $eq->estado === 'mantenimiento' => 'Mantenimiento',
                            $eq->estado === 'baja'          => 'Baja',
                            !is_null($eq->id_empleado)      => 'Asignado',
                            default                         => 'Almacén',
                        };
                        $responsable = $eq->empleado_nombre ?? $eq->nombre_usuario ?? '—';
                    @endphp
                    <tr class="hover:bg-gold/5 transition-colors cursor-pointer eq-row"
                        data-tipo="{{ $eq->tipo }}"
                        data-estado="{{ $estadoDisplay }}"
                        data-texto="{{ strtolower(($eq->area ?? '') . ' ' . $responsable . ' ' . ($eq->cpu_serie ?? '')) }}"
                        onclick="abrirPanelEquipo({{ $eq->id }})">
                        <td class="px-4 py-3 font-mono text-sm text-brand">{{ $eq->num_inventario ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase whitespace-nowrap
                                {{ $eq->tipo === 'Laptop' ? 'bg-status-free/10 text-status-free' : 'bg-status-active/10 text-status-active' }}">
                                {{ $eq->tipo }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @if($eq->cpu_marca || $eq->cpu_modelo)
                                <span class="font-medium">{{ $eq->cpu_marca }}</span>
                                <span class="text-muted"> {{ $eq->cpu_modelo }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-sm text-brand whitespace-nowrap">{{ $eq->cpu_serie ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-muted max-w-[160px] truncate" title="{{ $eq->area }}">{{ $eq->area ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm max-w-[160px] truncate" title="{{ $responsable }}">{{ $responsable }}</td>
                        <td class="px-4 py-3">
                            @php $estadoColors = [
                                'Asignado'      => 'bg-status-active/10 text-status-active',
                                'Almacén'       => 'bg-status-free/10 text-status-free',
                                'Mantenimiento' => 'bg-status-low/10 text-status-low',
                                'Baja'          => 'bg-muted/10 text-muted',
                            ]; @endphp
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $estadoColors[$estadoDisplay] ?? '' }}"
                                  id="estado-badge-{{ $eq->id }}">
                                {{ $estadoDisplay }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button onclick="event.stopPropagation(); abrirPanelEquipo({{ $eq->id }})"
                                    class="p-1.5 hover:bg-wash rounded text-muted hover:text-brand">
                                <span class="material-symbols-outlined text-sm">open_in_new</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-muted text-sm">Sin equipos registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- ---- TAB: INSUMOS ---- --}}
    <div id="tab-insumos" class="hidden">
        <div class="grid grid-cols-4 gap-4 mb-6">
            <div class="bg-canvas border border-border p-5 rounded-xl shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Total Items</p>
                <p class="text-2xl font-bold">{{ $totalInsumos }}</p>
                <div class="mt-3 flex items-center gap-1 text-status-active text-xs font-bold">
                    <span class="material-symbols-outlined text-sm">trending_up</span> Actualizado
                </div>
            </div>
            <div class="bg-canvas border border-border p-5 rounded-xl shadow-sm border-l-4 border-l-status-critical">
                <p class="text-[11px] font-bold uppercase tracking-wider text-status-critical mb-2">Stock Crítico</p>
                <p class="text-2xl font-bold">{{ $stockCritico }}</p>
                <p class="mt-3 text-xs text-muted">Requiere atención</p>
            </div>
            <div class="bg-canvas border border-border p-5 rounded-xl shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Salidas recientes</p>
                <p class="text-2xl font-bold">—</p>
                <p class="mt-3 text-xs text-muted">Últimas 24 horas</p>
            </div>
            <div class="bg-canvas border border-border p-5 rounded-xl shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Valor en Almacén</p>
                <p class="text-2xl font-bold">—</p>
                <p class="mt-3 text-xs text-muted">Solo insumos</p>
            </div>
        </div>

        <div class="bg-canvas border border-border rounded-xl overflow-hidden shadow-sm">
            <x-tabla-encabezado titulo="Inventario de Insumos" tab="insumos" exportUrl="{{ route('kardex.exportar', 'insumos') }}">
                <x-slot:filtros>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Buscar</label>
                        <input id="f-ins-texto" oninput="filtrarInsumos()" type="text" placeholder="Nombre o No. parte..."
                               class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand w-56">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Stock</label>
                        <select id="f-ins-stock" onchange="filtrarInsumos()"
                                class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand">
                            <option value="">Todos</option>
                            <option value="ok">OK</option>
                            <option value="critico">Crítico</option>
                        </select>
                    </div>
                </x-slot:filtros>
            </x-tabla-encabezado>

            <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-wash border-b border-border">
                    <tr>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Part Number</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Descripción</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Stock Mín.</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Stock Actual</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border" id="tbody-insumos">
                    @forelse($insumos as $ins)
                    @php $critico = $ins->stock_actual <= $ins->stock_minimo; @endphp
                    <tr class="hover:bg-gold/5 transition-colors ins-row"
                        data-texto="{{ strtolower($ins->nombre_insumo . ' ' . ($ins->numero_parte ?? '')) }}"
                        data-stock="{{ $critico ? 'critico' : 'ok' }}">
                        <td class="px-6 py-3 font-mono text-sm text-brand">{{ $ins->numero_parte ?? '—' }}</td>
                        <td class="px-6 py-3 text-sm font-medium">{{ $ins->nombre_insumo }}</td>
                        <td class="px-6 py-3 font-mono text-sm text-center">{{ $ins->stock_minimo }}</td>
                        <td class="px-6 py-3 font-mono text-sm font-bold text-center">{{ $ins->stock_actual }}</td>
                        <td class="px-6 py-3">
                            @if(!$critico)
                            <span class="bg-status-active/10 text-status-active px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">OK</span>
                            @else
                            <span class="bg-status-critical/10 text-status-critical px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Crítico</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-muted text-sm">Sin insumos registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- ---- TAB: RESGUARDOS ---- --}}
    <div id="tab-resguardos" class="hidden">
        <div class="bg-canvas border border-border rounded-xl overflow-hidden shadow-sm">
            <x-tabla-encabezado titulo="Documentos de Resguardo" tab="resguardos">
                <x-slot:acciones>
                    <a href="{{ route('kardex.resguardo.subir') }}"
                       class="px-3 py-1.5 bg-brand text-white rounded text-sm font-bold flex items-center gap-2 hover:opacity-90 transition-colors">
                        <span class="material-symbols-outlined text-sm">upload_file</span>
                        Registrar equipo
                    </a>
                </x-slot:acciones>
                <x-slot:filtros>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Tipo</label>
                        <select id="f-rsg-tipo" onchange="filtrarResguardos()"
                                class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand">
                            <option value="">Todos</option>
                            <option>Laptop</option>
                            <option>PC Avanzada</option>
                            <option>PC Especializada</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">PDF</label>
                        <select id="f-rsg-pdf" onchange="filtrarResguardos()"
                                class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand">
                            <option value="">Todos</option>
                            <option value="si">Con PDF</option>
                            <option value="no">Sin PDF</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Área / Responsable</label>
                        <input id="f-rsg-texto" oninput="filtrarResguardos()" type="text" placeholder="Buscar..."
                               class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand w-52">
                    </div>
                </x-slot:filtros>
            </x-tabla-encabezado>

            <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-wash border-b border-border">
                    <tr>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">ID</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Tipo</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Marca / Modelo</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">No. Serie</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Responsable</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Área</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">PDF</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Registrado</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted text-right">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border" id="tbody-resguardos">
                    @forelse($equipos as $eq)
                    @php
                        $responsable = $eq->empleado_nombre ?? $eq->nombre_usuario ?? '—';
                        $tienePdf    = !empty($eq->pdf_resguardo);
                    @endphp
                    <tr class="hover:bg-gold/5 transition-colors cursor-pointer rsg-row"
                        data-tipo="{{ $eq->tipo }}"
                        data-pdf="{{ $tienePdf ? 'si' : 'no' }}"
                        data-texto="{{ strtolower(($eq->area ?? '') . ' ' . $responsable) }}"
                        onclick="abrirPanelEquipo({{ $eq->id }})">
                        <td class="px-4 py-3 font-mono text-xs text-muted">#{{ $eq->id }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                {{ $eq->tipo === 'Laptop' ? 'bg-status-free/10 text-status-free' : 'bg-status-active/10 text-status-active' }}">
                                {{ $eq->tipo }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="font-medium">{{ $eq->cpu_marca }}</span>
                            <span class="text-muted"> {{ $eq->cpu_modelo }}</span>
                        </td>
                        <td class="px-4 py-3 font-mono text-sm text-brand">{{ $eq->cpu_serie ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm max-w-[150px] truncate" title="{{ $responsable }}">{{ $responsable }}</td>
                        <td class="px-4 py-3 text-sm text-muted max-w-[150px] truncate" title="{{ $eq->area }}">{{ $eq->area ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($tienePdf)
                            <span class="flex items-center gap-1 text-status-active text-xs font-bold">
                                <span class="material-symbols-outlined" style="font-size:16px">picture_as_pdf</span> PDF
                            </span>
                            @else
                            <span class="text-muted text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-muted">
                            {{ $eq->created_at ? \Carbon\Carbon::parse($eq->created_at)->format('d/m/Y') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button onclick="event.stopPropagation(); abrirPanelEquipo({{ $eq->id }})"
                                    class="p-1.5 hover:bg-wash rounded text-muted hover:text-brand">
                                <span class="material-symbols-outlined text-sm">open_in_new</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="px-6 py-10 text-center text-muted text-sm">Sin equipos registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

</div>

{{-- ══ Panel lateral: Detalle de equipo ══ --}}
<div class="fixed top-0 right-0 h-screen w-[440px] bg-canvas shadow-2xl border-l border-gold z-[60] flex flex-col translate-x-full transition-transform duration-300" id="equipo-panel">
    {{-- Header del panel --}}
    <div class="p-5 border-b border-border bg-surface flex justify-between items-start shrink-0">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-muted" id="panel-tipo-badge">—</p>
            <h4 class="text-xl font-bold text-brand" id="panel-serie">—</h4>
            <p class="text-sm text-muted mt-0.5" id="panel-marca-modelo">—</p>
        </div>
        <button class="text-muted hover:text-brand p-1" onclick="cerrarPanelEquipo()">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    {{-- Cuerpo del panel --}}
    <div class="flex-1 overflow-y-auto p-5 space-y-4 custom-scrollbar" id="panel-cuerpo">
        <div class="flex items-center justify-center py-16 text-muted">
            <span class="material-symbols-outlined text-4xl animate-spin">progress_activity</span>
        </div>
    </div>

    {{-- Footer --}}
    <div class="p-5 border-t border-border shrink-0 flex gap-3 items-center" id="panel-footer">
        <a id="panel-pdf-link" href="#" target="_blank"
           class="flex-1 py-2.5 bg-brand text-white font-bold rounded text-sm flex items-center justify-center gap-2 hover:opacity-90 hidden">
            <span class="material-symbols-outlined text-sm">download</span> Descargar PDF
        </a>
        <button onclick="cerrarPanelEquipo()"
                class="flex-1 py-2.5 border border-border rounded text-sm font-bold text-muted hover:bg-wash transition-colors">
            Cerrar
        </button>
    </div>
</div>

<div class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[55] hidden" id="kardex-backdrop"
     onclick="cerrarPanelEquipo()"></div>

<style>
.detail-field { display: flex; flex-direction: column; gap: 2px; }
.detail-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: var(--color-muted); }
.detail-value { font-size: 13px; color: #1a1a1a; }
.detail-value.mono { font-family: 'JetBrains Mono', monospace; font-size: 12px; color: var(--color-brand); }
</style>

<script>
const ACTIVE_TAB  = 'px-6 py-2 rounded-md text-sm font-bold transition-all bg-canvas text-brand shadow-sm';
const INACTIVE_TAB= 'px-6 py-2 rounded-md text-sm font-bold transition-all text-muted hover:bg-surface-high';
let currentTab    = 'equipos';

function switchKardexTab(tab, btn) {
    ['equipos', 'insumos', 'resguardos'].forEach(t => {
        document.getElementById('tab-' + t).classList.toggle('hidden', t !== tab);
        document.getElementById('tab-btn-' + t).className = t === tab ? ACTIVE_TAB : INACTIVE_TAB;
    });
    currentTab = tab;
}

// ── Filtros ──────────────────────────────────────────────────────────
// toggleFiltros y limpiarFiltros vienen del componente x-tabla-encabezado

function filtrarEquipos() {
    const tipo   = document.getElementById('f-eq-tipo').value;
    const estado = document.getElementById('f-eq-estado').value;
    const texto  = document.getElementById('f-eq-texto').value.toLowerCase();
    let vis = 0;
    document.querySelectorAll('.eq-row').forEach(row => {
        const ok = (!tipo   || row.dataset.tipo   === tipo)
                && (!estado || row.dataset.estado  === estado)
                && (!texto  || row.dataset.texto.includes(texto));
        row.classList.toggle('hidden', !ok);
        if (ok) vis++;
    });
    const count = document.getElementById('f-eq-count');
    if (count) count.textContent = `${vis} resultado${vis !== 1 ? 's' : ''}`;
}

function filtrarInsumos() {
    const texto = document.getElementById('f-ins-texto').value.toLowerCase();
    const stock = document.getElementById('f-ins-stock').value;
    let vis = 0;
    document.querySelectorAll('.ins-row').forEach(row => {
        const ok = (!texto || row.dataset.texto.includes(texto))
                && (!stock || row.dataset.stock === stock);
        row.classList.toggle('hidden', !ok);
        if (ok) vis++;
    });
    const count = document.getElementById('f-ins-count');
    if (count) count.textContent = `${vis} resultado${vis !== 1 ? 's' : ''}`;
}

function filtrarResguardos() {
    const tipo  = document.getElementById('f-rsg-tipo').value;
    const pdf   = document.getElementById('f-rsg-pdf').value;
    const texto = document.getElementById('f-rsg-texto').value.toLowerCase();
    let vis = 0;
    document.querySelectorAll('.rsg-row').forEach(row => {
        const ok = (!tipo  || row.dataset.tipo  === tipo)
                && (!pdf   || row.dataset.pdf   === pdf)
                && (!texto || row.dataset.texto.includes(texto));
        row.classList.toggle('hidden', !ok);
        if (ok) vis++;
    });
    const count = document.getElementById('f-rsg-count');
    if (count) count.textContent = `${vis} resultado${vis !== 1 ? 's' : ''}`;
}

// ── Panel lateral: detalle de equipo ─────────────────────────────────
let panelEquipoId = null;

async function abrirPanelEquipo(id) {
    panelEquipoId = id;
    document.getElementById('equipo-panel').classList.remove('translate-x-full');
    document.getElementById('kardex-backdrop').classList.remove('hidden');
    document.getElementById('panel-tipo-badge').textContent = '…';
    document.getElementById('panel-serie').textContent      = '…';
    document.getElementById('panel-marca-modelo').textContent = '';
    document.getElementById('panel-cuerpo').innerHTML =
        '<div class="flex items-center justify-center py-16 text-muted"><span class="material-symbols-outlined text-4xl animate-spin">progress_activity</span></div>';
    document.getElementById('panel-pdf-link').classList.add('hidden');

    const eq = await fetch(`/kardex/equipo/${id}`).then(r => r.json());

    // Header
    document.getElementById('panel-tipo-badge').textContent    = eq.tipo ?? '—';
    document.getElementById('panel-serie').textContent         = eq.cpu_serie ?? 'Sin serie';
    document.getElementById('panel-marca-modelo').textContent  = [eq.cpu_marca, eq.cpu_modelo].filter(Boolean).join(' ') || '—';

    // PDF link
    const pdfLink = document.getElementById('panel-pdf-link');
    if (eq.pdf_resguardo) {
        pdfLink.href = `/kardex/equipo/${eq.id}/pdf`;
        pdfLink.classList.remove('hidden');
    }

    // Calcular estado display
    const estadoDisplay = eq.estado === 'mantenimiento' ? 'Mantenimiento'
        : eq.estado === 'baja' ? 'Baja'
        : eq.id_empleado ? 'Asignado'
        : 'Almacén';

    // Construir cuerpo
    const f = (label, value, mono = false) => `
        <div class="detail-field">
            <span class="detail-label">${label}</span>
            <span class="detail-value${mono ? ' mono' : ''}">${value ?? '—'}</span>
        </div>`;

    let html = `
        {{-- Identificación --}}
        <div class="bg-[#F9FAFB] border border-border rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-3">Identificación</p>
            <div class="grid grid-cols-2 gap-3">
                ${f('No. Inventario', eq.num_inventario, true)}
                ${f('Consecutivo', eq.consecutivo, true)}
                ${f('Área', eq.area)}
                ${f('Nombre en documento', eq.nombre_usuario)}
            </div>
        </div>

        {{-- CPU --}}
        <div class="bg-[#F9FAFB] border border-border rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-3">CPU / Equipo principal</p>
            <div class="grid grid-cols-2 gap-3">
                ${f('Marca', eq.cpu_marca)}
                ${f('Modelo', eq.cpu_modelo)}
                ${f('No. Serie', eq.cpu_serie, true)}
                ${f('IPv4', eq.ipv4_real || eq.ipv4, true)}
                ${f('MAC', eq.mac_real || eq.mac, true)}
            </div>
        </div>`;

    // Periféricos laptop
    const laptopFields = [
        ['Serie cargador', eq.cargador_serie, true],
        ['Docking marca', eq.docking_marca],
        ['Docking modelo', eq.docking_modelo],
        ['Docking serie', eq.docking_serie, true],
    ].filter(([, v]) => v);
    if (eq.tipo === 'Laptop' && laptopFields.length) {
        html += `<div class="bg-[#F9FAFB] border border-border rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-3">Periféricos — Laptop</p>
            <div class="grid grid-cols-2 gap-3">
                ${laptopFields.map(([l,v,m]) => f(l,v,m)).join('')}
            </div></div>`;
    }

    // Periféricos PC
    const pcFields = [
        ['Monitor marca', eq.monitor_marca],
        ['Monitor modelo', eq.monitor_modelo],
        ['Monitor serie', eq.monitor_serie, true],
        ['Teclado serie', eq.teclado_serie, true],
        ['Mouse serie', eq.mouse_serie, true],
        ['Nobreak marca', eq.nobreak_marca],
        ['Nobreak modelo', eq.nobreak_modelo],
        ['Nobreak serie', eq.nobreak_serie, true],
    ].filter(([, v]) => v);
    if ((eq.tipo === 'PC Avanzada' || eq.tipo === 'PC Especializada') && pcFields.length) {
        html += `<div class="bg-[#F9FAFB] border border-border rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-3">Periféricos — PC</p>
            <div class="grid grid-cols-2 gap-3">
                ${pcFields.map(([l,v,m]) => f(l,v,m)).join('')}
            </div></div>`;
    }

    // Empleado vinculado
    html += `<div class="bg-[#F9FAFB] border border-border rounded-xl p-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-3">Responsable / Estado</p>
        <div class="grid grid-cols-1 gap-3">
            ${f('Empleado vinculado', eq.empleado_nombre ? `${eq.empleado_nombre} &lt;${eq.empleado_correo ?? ''}&gt;` : 'Sin vincular')}
            <div class="detail-field">
                <span class="detail-label">Estado del equipo</span>
                <div class="flex items-center gap-2 mt-1">
                    <select id="select-estado-panel" class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand"
                            onchange="">
                        <option value="" ${!eq.estado ? 'selected' : ''}>Automático (${estadoDisplay})</option>
                        <option value="mantenimiento" ${eq.estado === 'mantenimiento' ? 'selected' : ''}>Mantenimiento</option>
                        <option value="baja" ${eq.estado === 'baja' ? 'selected' : ''}>Baja</option>
                    </select>
                    <button onclick="guardarEstado(${eq.id})"
                            class="px-3 py-1.5 bg-brand text-white text-xs font-bold rounded hover:opacity-90">
                        Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>`;

    if (eq.observaciones) {
        html += `<div class="bg-[#F9FAFB] border border-border rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-2">Observaciones</p>
            <p class="text-sm text-muted">${eq.observaciones}</p>
        </div>`;
    }

    html += `<p class="text-[11px] text-muted text-center pb-2">
        Registrado: ${eq.created_at ? new Date(eq.created_at).toLocaleDateString('es-MX') : '—'}
    </p>`;

    document.getElementById('panel-cuerpo').innerHTML = html;
}

function cerrarPanelEquipo() {
    document.getElementById('equipo-panel').classList.add('translate-x-full');
    document.getElementById('kardex-backdrop').classList.add('hidden');
    panelEquipoId = null;
}

async function guardarEstado(id) {
    const select = document.getElementById('select-estado-panel');
    const nuevoEstado = select?.value ?? '';
    const r = await fetch(`/kardex/equipo/${id}/estado`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: JSON.stringify({ estado: nuevoEstado }),
    });
    if (r.ok) {
        // Actualizar badge en la tabla
        const badge = document.getElementById(`estado-badge-${id}`);
        if (badge) {
            const labelMap = { mantenimiento: 'Mantenimiento', baja: 'Baja', '': null };
            badge.textContent = labelMap[nuevoEstado] ?? badge.textContent;
        }
        select.closest('.detail-field')?.querySelector('button')?.classList.add('opacity-50');
        setTimeout(() => select.closest('.detail-field')?.querySelector('button')?.classList.remove('opacity-50'), 1000);
    }
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') cerrarPanelEquipo();
});

// Deep-link: ?open=id abre el panel del equipo directamente
(function () {
    const id = new URLSearchParams(location.search).get('open');
    if (id) abrirPanelEquipo(parseInt(id));
})();
</script>
</x-layouts.app>
