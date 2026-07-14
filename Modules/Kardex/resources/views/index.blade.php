<x-layouts.app title="Kardex — IMJUVE CRM">
<div class="p-8" id="kardex-page">

    {{-- Header --}}
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="text-[32px] font-bold leading-10 tracking-tight text-brand">Inventario de equipos y movimientos</h2>
            <p class="text-muted text-sm mt-1">Control de inventario técnico y asignación institucional de recursos.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex gap-2 bg-[#efeded] rounded-lg p-1">
                <button id="tab-btn-equipos" onclick="switchKardexTab('equipos', this)"
                        class="px-6 py-2 rounded-md text-sm font-bold transition-all bg-canvas text-brand shadow-sm">
                    Equipos
                </button>
                <button id="tab-btn-resguardos" onclick="switchKardexTab('resguardos', this)"
                        class="px-6 py-2 rounded-md text-sm font-bold transition-all text-muted hover:bg-surface-high">
                    Resguardos
                </button>
                <button id="tab-btn-movimientos" onclick="switchKardexTab('movimientos', this)"
                        class="px-6 py-2 rounded-md text-sm font-bold transition-all text-muted hover:bg-surface-high">
                    Movimientos
                </button>
            </div>
        </div>
    </div>

    {{-- ---- TAB: EQUIPOS (activo por defecto) ---- --}}
    <div id="tab-equipos">
        {{-- Stats: equipos asignados por categoría --}}
        @php $equipStats = [
            ['label'=>'Laptops asignadas',       'value'=>$laptopsAsignadas,          'icon'=>'laptop',          'color'=>'var(--color-status-free)'],
            ['label'=>'PC Avanzadas asignadas',   'value'=>$pcAvanzadasAsignadas,      'icon'=>'desktop_windows', 'color'=>'var(--color-status-active)'],
            ['label'=>'PC Especializadas asig.',  'value'=>$pcEspecializadasAsignadas, 'icon'=>'computer',        'color'=>'var(--color-brand)'],
            ['label'=>'Teléfonos asignados',      'value'=>$telefonosAsignados,        'icon'=>'phone',           'color'=>'#7c3aed'],
            ['label'=>'Impresoras asignadas',     'value'=>$impresorasAsignadas,       'icon'=>'print',           'color'=>'var(--color-status-low)'],
        ]; @endphp
        <div class="grid grid-cols-5 gap-4 mb-6">
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
                            <option>Telefono</option>
                            <option>Impresora</option>
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
                            !is_null($eq->user_id)          => 'Asignado',
                            default                         => 'Almacén',
                        };
                        $responsable = $eq->empleado_nombre ?? $eq->nombre_usuario ?? '—';
                        $usuario     = $eq->usuario_nombre ?? null;
                        $usuarioDif  = $usuario && $usuario !== $responsable;
                    @endphp
                    <tr class="hover:bg-gold/5 transition-colors cursor-pointer eq-row"
                        data-id="{{ $eq->id }}"
                        data-tipo="{{ $eq->tipo }}"
                        data-estado="{{ $estadoDisplay }}"
                        data-texto="{{ strtolower(($eq->area ?? '') . ' ' . $responsable . ' ' . ($usuario ?? '') . ' ' . ($eq->cpu_serie ?? '')) }}"
                        onclick="abrirPanelEquipo({{ $eq->id }})">
                        <td class="px-4 py-3 font-mono text-sm text-brand">{{ $eq->num_inventario ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @php $tipoCls = match($eq->tipo) {
                                'Laptop'           => 'bg-status-free/10 text-status-free',
                                'Telefono'         => 'bg-purple-100 text-purple-600',
                                default            => 'bg-status-active/10 text-status-active',
                            }; @endphp
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase whitespace-nowrap {{ $tipoCls }}">
                                {{ $eq->tipo === 'Telefono' ? 'Teléfono' : $eq->tipo }}
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
                        <td class="px-4 py-3 text-sm max-w-[160px]" id="responsable-cell-{{ $eq->id }}">
                            <p class="truncate" title="{{ $responsable }}">{{ $responsable }}</p>
                            @if($usuarioDif)
                                <p class="truncate text-xs text-muted mt-0.5" title="Usuario: {{ $usuario }}">
                                    <span class="text-brand/60">↳</span> {{ $usuario }}
                                </p>
                            @endif
                        </td>
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
                    @if($impresoras->isEmpty())
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-muted text-sm">Sin equipos registrados</td>
                    </tr>
                    @endif
                    @endforelse
                    {{-- Impresoras como categoría dentro de equipos --}}
                    @foreach($impresoras as $imp)
                    @php
                        $impEstado = $imp->user_id ? 'Asignado' : 'Almacén';
                        $impResponsable = $imp->responsable_nombre ?? '—';
                    @endphp
                    <tr class="hover:bg-gold/5 transition-colors cursor-pointer eq-row"
                        data-id="{{ $imp->id_impresora }}"
                        data-tipo="Impresora"
                        data-estado="{{ $impEstado }}"
                        data-estado-raw="{{ $imp->estado ?? '' }}"
                        data-texto="{{ strtolower(($imp->area ?? '') . ' ' . $impResponsable . ' ' . ($imp->serie ?? '')) }}"
                        data-area="{{ $imp->area ?? '' }}"
                        data-serie="{{ $imp->serie ?? '' }}"
                        data-marca-modelo="{{ trim(($imp->marca ?? '') . ' ' . ($imp->modelo ?? '') . ($imp->firmware ? ' / ' . $imp->firmware : '')) }}"
                        data-responsable="{{ $impResponsable }}"
                        data-correo="{{ $imp->responsable_correo ?? '' }}"
                        data-ip="{{ $imp->ip_address ?? '' }}"
                        onclick="abrirPanelImpresora(this)">
                        <td class="px-4 py-3 font-mono text-sm text-brand">—</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase bg-status-low/10 text-status-low">
                                Impresora
                            </span>
                        </td>
                        <td class="px-4 py-3 text-sm">
                            <span class="font-medium">{{ $imp->marca }}</span>
                            <span class="text-muted"> {{ $imp->modelo }}</span>
                        </td>
                        <td class="px-4 py-3 font-mono text-sm text-brand whitespace-nowrap">{{ $imp->serie ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm text-muted max-w-[160px] truncate" title="{{ $imp->area }}">{{ $imp->area ?? '—' }}</td>
                        <td class="px-4 py-3 text-sm max-w-[160px] truncate" title="{{ $impResponsable }}">{{ $impResponsable }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $impEstado === 'Asignado' ? 'bg-status-active/10 text-status-active' : 'bg-status-free/10 text-status-free' }}">
                                {{ $impEstado }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <button onclick="event.stopPropagation(); abrirPanelImpresora(this.closest('tr'))"
                                    class="p-1.5 hover:bg-wash rounded text-muted hover:text-brand">
                                <span class="material-symbols-outlined text-sm">open_in_new</span>
                            </button>
                        </td>
                    </tr>
                    @endforeach
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
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Estado</label>
                        <select id="f-rsg-estado" onchange="filtrarResguardos()"
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
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Estado</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Registrado</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted text-right">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border" id="tbody-resguardos">
                    @forelse($resguardos as $eq)
                    @php
                        $responsable = $eq->empleado_nombre ?? $eq->nombre_usuario ?? '—';
                        $estadoDisplay = match(true) {
                            $eq->estado === 'mantenimiento' => 'Mantenimiento',
                            $eq->estado === 'baja'          => 'Baja',
                            !is_null($eq->user_id)          => 'Asignado',
                            default                         => 'Almacén',
                        };
                        $estadoColors = [
                            'Asignado'      => 'bg-status-active/10 text-status-active',
                            'Almacén'       => 'bg-status-free/10 text-status-free',
                            'Mantenimiento' => 'bg-status-low/10 text-status-low',
                            'Baja'          => 'bg-muted/10 text-muted',
                        ];
                    @endphp
                    <tr class="hover:bg-gold/5 transition-colors cursor-pointer rsg-row"
                        data-id="{{ $eq->id }}"
                        data-tipo="{{ $eq->tipo }}"
                        data-estado="{{ $estadoDisplay }}"
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
                        <td class="px-4 py-3 text-sm max-w-[150px] truncate" title="{{ $responsable }}" id="responsable-cell-rsg-{{ $eq->id }}">{{ $responsable }}</td>
                        <td class="px-4 py-3 text-sm text-muted max-w-[150px] truncate" title="{{ $eq->area }}">{{ $eq->area ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $estadoColors[$estadoDisplay] ?? '' }}"
                                  id="estado-badge-rsg-{{ $eq->id }}">
                                {{ $estadoDisplay }}
                            </span>
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
                        <td colspan="9" class="px-6 py-10 text-center text-muted text-sm">Sin resguardos con PDF registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </div>
    </div>

    {{-- ---- TAB: MOVIMIENTOS ---- --}}
    <div id="tab-movimientos" class="hidden">
        <div class="bg-canvas border border-border rounded-xl overflow-hidden shadow-sm">
            <x-tabla-encabezado
                titulo="Historial de movimientos"
                tab="movimientos"
                :importar="false"
                exportUrl="{{ route('kardex.exportar', 'movimientos') }}">
                <x-slot:filtros>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Activo</label>
                        <select id="f-mov-activo" onchange="filtrarMovimientos()"
                                class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand">
                            <option value="">Todos</option>
                            <option value="equipo">Equipo</option>
                            <option value="impresora">Impresora</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Evento</label>
                        <select id="f-mov-evento" onchange="filtrarMovimientos()"
                                class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand">
                            <option value="">Todos</option>
                            <option>Entrada</option>
                            <option>Asignación</option>
                            <option>Reasignación</option>
                            <option>Almacén</option>
                            <option>Mantenimiento</option>
                            <option>Baja</option>
                            <option>Reingreso</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Buscar</label>
                        <input id="f-mov-texto" oninput="filtrarMovimientos()" type="text" placeholder="Nombre, serie, área…"
                               class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand w-44">
                    </div>
                </x-slot:filtros>
            </x-tabla-encabezado>

            <div class="overflow-x-auto">
            <table class="w-full text-left" id="tabla-movimientos">
                <thead class="bg-wash border-b border-border">
                    <tr>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Fecha</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Tipo</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Activo</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Evento</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Origen → Destino</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Estado</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Notas</th>
                        <th class="px-4 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Registrado por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border" id="tbody-movimientos">
                    <tr>
                        <td colspan="8" class="px-6 py-10 text-center text-muted text-sm">
                            <span class="material-symbols-outlined text-2xl animate-spin align-middle mr-2">progress_activity</span>
                            Cargando movimientos…
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
        </div>
    </div>

{{-- ══ Panel lateral: detalle de equipo ══ --}}
<div class="fixed top-0 right-0 h-screen w-[440px] bg-canvas shadow-2xl border-l border-gold z-[60] flex flex-col translate-x-full transition-transform duration-300" id="equipo-panel">
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
    <div class="flex-1 overflow-y-auto p-5 space-y-4 custom-scrollbar" id="panel-cuerpo">
        <div class="flex items-center justify-center py-16 text-muted">
            <span class="material-symbols-outlined text-4xl animate-spin">progress_activity</span>
        </div>
    </div>
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
const NETWORK_URL = "{{ route('network.index') }}";
const ACTIVE_TAB  = 'px-6 py-2 rounded-md text-sm font-bold transition-all bg-canvas text-brand shadow-sm';
const INACTIVE_TAB= 'px-6 py-2 rounded-md text-sm font-bold transition-all text-muted hover:bg-surface-high';
let currentTab    = 'equipos';

function switchKardexTab(tab, btn) {
    ['equipos', 'resguardos', 'movimientos'].forEach(t => {
        document.getElementById('tab-' + t).classList.toggle('hidden', t !== tab);
        document.getElementById('tab-btn-' + t).className = t === tab ? ACTIVE_TAB : INACTIVE_TAB;
    });
    currentTab = tab;
    if (tab === 'movimientos' && !window._movimientosCargados) cargarMovimientos();
}

// ── Tab Movimientos ───────────────────────────────────────────────────
let _todosMovimientos = [];

async function cargarMovimientos() {
    const r = await fetch('/kardex/movimientos/json');
    _todosMovimientos = await r.json();
    window._movimientosCargados = true;
    renderMovimientos(_todosMovimientos);
}

function filtrarMovimientos() {
    const activo  = document.getElementById('f-mov-activo').value;
    const evento  = document.getElementById('f-mov-evento').value;
    const texto   = document.getElementById('f-mov-texto').value.toLowerCase();

    const filtrados = _todosMovimientos.filter(m =>
        (!activo  || m.tipo_activo  === activo)
     && (!evento  || m.tipo_evento  === evento)
     && (!texto   || (m.texto_busqueda ?? '').includes(texto))
    );

    renderMovimientos(filtrados);
}

const MOV_COLORS = {
    'Entrada':       'bg-status-free/10 text-status-free',
    'Asignación':    'bg-status-active/10 text-status-active',
    'Reasignación':  'bg-brand/10 text-brand',
    'Almacén':       'bg-muted/10 text-muted',
    'Mantenimiento': 'bg-status-low/10 text-status-low',
    'Baja':          'bg-red-100 text-red-600',
    'Reingreso':     'bg-status-free/10 text-status-free',
};

function renderMovimientos(movs) {
    const tbody = document.getElementById('tbody-movimientos');
    const count = document.getElementById('f-movimientos-count');
    if (count) count.textContent = `${movs.length} registro${movs.length !== 1 ? 's' : ''}`;

    if (!movs.length) {
        tbody.innerHTML = `<tr><td colspan="8" class="px-6 py-10 text-center text-muted text-sm">Sin movimientos registrados.</td></tr>`;
        return;
    }

    tbody.innerHTML = movs.map(m => {
        const fecha = m.created_at
            ? new Date(m.created_at).toLocaleDateString('es-MX', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' })
            : '—';
        const cls   = MOV_COLORS[m.tipo_evento] ?? 'bg-muted/10 text-muted';
        const tipoCls = m.tipo_activo === 'equipo'
            ? 'bg-status-active/10 text-status-active'
            : 'bg-status-low/10 text-status-low';
        const origen  = m.origen  || '—';
        const destino = m.destino || '—';

        return `<tr class="hover:bg-gold/5 transition-colors mov-row"
                    data-activo="${m.tipo_activo}"
                    data-evento="${m.tipo_evento}">
            <td class="px-4 py-3 text-xs text-muted whitespace-nowrap">${fecha}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${tipoCls}">
                    ${m.tipo_activo === 'equipo' ? 'Equipo' : 'Impresora'}
                </span>
            </td>
            <td class="px-4 py-3 text-sm">
                <p class="font-medium text-brand font-mono text-xs">${m.activo_serie ?? '—'}</p>
                <p class="text-muted text-xs">${m.activo_desc ?? ''}</p>
            </td>
            <td class="px-4 py-3">
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${cls}">${m.tipo_evento}</span>
            </td>
            <td class="px-4 py-3 text-xs">
                <span class="text-muted">${origen}</span>
                ${origen !== '—' || destino !== '—' ? ' <span class="text-muted">→</span> ' : ''}
                <span class="font-medium">${destino}</span>
            </td>
            <td class="px-4 py-3 text-xs text-muted">${m.estado_equipo ?? '—'}</td>
            <td class="px-4 py-3 text-xs text-muted max-w-[180px] truncate" title="${m.notas ?? ''}">${m.notas ?? '—'}</td>
            <td class="px-4 py-3 text-xs text-muted">${m.registrado_email ?? '—'}</td>
        </tr>`;
    }).join('');
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


// ── Panel lateral: impresora (datos ya en data-* del row) ────────────
let panelImpresoraId = null;

function abrirPanelImpresora(row) {
    const d = row.dataset;
    panelImpresoraId  = d.id;
    panelEquipoId     = null;
    panelEquipoUserId = null;

    document.getElementById('equipo-panel').classList.remove('translate-x-full');
    document.getElementById('kardex-backdrop').classList.remove('hidden');
    document.getElementById('panel-tipo-badge').textContent    = 'Impresora';
    document.getElementById('panel-serie').textContent         = d.serie || 'Sin serie';
    document.getElementById('panel-marca-modelo').textContent  = d.marcaModelo || '—';
    document.getElementById('panel-pdf-link').classList.add('hidden');

    const f = (label, value, mono = false) => `
        <div class="detail-field">
            <span class="detail-label">${label}</span>
            <span class="detail-value${mono ? ' mono' : ''}">${value || '—'}</span>
        </div>`;

    const ipHtml = d.ip
        ? `<a href="http://${d.ip}" target="_blank" rel="noopener"
              class="font-mono text-brand hover:underline flex items-center gap-1">
               ${d.ip} <span class="material-symbols-outlined" style="font-size:15px">open_in_new</span>
           </a>`
        : '<span class="text-muted text-sm">Sin IP asignada</span>';

    const estadoRaw = d.estadoRaw || '';
    const estadoDisplay = estadoRaw === 'mantenimiento' ? 'Mantenimiento'
        : estadoRaw === 'baja' ? 'Baja'
        : d.responsable ? 'Asignado' : 'Almacén';

    document.getElementById('panel-cuerpo').innerHTML = `
        <div class="bg-[#F9FAFB] border border-border rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-3">Identificación</p>
            <div class="grid grid-cols-2 gap-3">
                ${f('Área', d.area)}
                ${f('No. Serie', d.serie, true)}
                ${f('Marca / Modelo', d.marcaModelo)}
            </div>
        </div>
        <div class="bg-[#F9FAFB] border border-border rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-3">Red</p>
            <div class="detail-field">
                <span class="detail-label">Dirección IP</span>
                <div class="mt-0.5">${ipHtml}</div>
            </div>
        </div>
        <div class="bg-[#F9FAFB] border border-border rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-3">Responsable / Estado</p>
            <div class="grid grid-cols-1 gap-3">
                ${f('Nombre', d.responsable || 'Sin asignar')}
                ${d.correo ? f('Correo', d.correo) : ''}
                <div class="detail-field">
                    <span class="detail-label">Estado del equipo</span>
                    <div class="flex items-center gap-2 mt-1">
                        <select id="select-estado-impresora"
                                class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand">
                            <option value="" ${!estadoRaw ? 'selected' : ''}>Automático (${estadoDisplay})</option>
                            <option value="almacen">Almacén (desvincula responsable)</option>
                            <option value="mantenimiento" ${estadoRaw === 'mantenimiento' ? 'selected' : ''}>Mantenimiento</option>
                            <option value="baja" ${estadoRaw === 'baja' ? 'selected' : ''}>Baja</option>
                        </select>
                        <button onclick="guardarEstadoImpresora()"
                                class="px-3 py-1.5 bg-brand text-white text-xs font-bold rounded hover:opacity-90">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>`;

    // Cargar historial de movimientos al final del panel
    fetch(`/kardex/impresora/${panelImpresoraId}/historial`)
        .then(r => r.json())
        .then(movs => {
            const cuerpo = document.getElementById('panel-cuerpo');
            if (cuerpo) cuerpo.insertAdjacentHTML('beforeend', renderHistorial(movs));
        });
}

// ── Historial de movimientos — función compartida ─────────────────────
function renderHistorial(movs) {
    const colorEvento = {
        'Entrada':       'bg-status-free/10 text-status-free',
        'Asignación':    'bg-status-active/10 text-status-active',
        'Reasignación':  'bg-brand/10 text-brand',
        'Almacén':       'bg-muted/10 text-muted',
        'Mantenimiento': 'bg-status-low/10 text-status-low',
        'Baja':          'bg-red-100 text-red-600',
        'Reingreso':     'bg-status-free/10 text-status-free',
    };

    const filas = movs.length
        ? movs.map(m => {
            const fecha = m.created_at
                ? new Date(m.created_at).toLocaleDateString('es-MX', { day:'2-digit', month:'short', year:'numeric' })
                : '—';
            const cls = colorEvento[m.tipo_evento] ?? 'bg-muted/10 text-muted';
            const origen  = m.origen  ? `<span class="text-muted">${m.origen}</span> →` : '';
            const destino = m.destino ? `<span class="font-medium">${m.destino}</span>` : '';
            const estado  = m.estado_equipo ? `<span class="text-[10px] text-muted"> · ${m.estado_equipo}</span>` : '';
            const notas   = m.notas ? `<p class="text-[11px] text-muted mt-0.5 ml-5">${m.notas}</p>` : '';
            const por     = m.registrado_email ? `<p class="text-[10px] text-muted/60 ml-5 mt-0.5">Por: ${m.registrado_email}</p>` : '';

            return `<div class="relative pl-5 border-l-2 border-border pb-4 last:pb-0">
                <span class="absolute -left-[5px] top-1 w-2 h-2 rounded-full bg-border"></span>
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-[10px] font-bold text-muted">${fecha}</span>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold uppercase ${cls}">${m.tipo_evento}</span>
                    ${estado}
                </div>
                <p class="text-xs mt-0.5">${origen} ${destino}</p>
                ${notas}${por}
            </div>`;
        }).join('')
        : '<p class="text-xs text-muted">Sin movimientos registrados.</p>';

    return `<div class="bg-[#F9FAFB] border border-border rounded-xl p-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-3 flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">history</span>
            Historial de movimientos
        </p>
        <div class="space-y-3">${filas}</div>
    </div>`;
}

async function guardarEstadoImpresora() {
    const select = document.getElementById('select-estado-impresora');
    const nuevoEstado = select?.value ?? '';
    const r = await fetch(`/kardex/impresora/${panelImpresoraId}/estado`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: JSON.stringify({ estado: nuevoEstado }),
    });
    if (!r.ok) return;

    const labelMap = { mantenimiento: 'Mantenimiento', baja: 'Baja', almacen: 'Almacén' };
    const estadoDisplay = labelMap[nuevoEstado] ?? (nuevoEstado === 'almacen' ? 'Almacén' : 'Almacén');

    // Actualizar badge en la tabla
    const fila = document.querySelector(`tr[data-id="${panelImpresoraId}"]`);
    if (fila) {
        fila.dataset.estadoRaw = nuevoEstado === 'almacen' ? '' : nuevoEstado;
        fila.dataset.estado    = labelMap[nuevoEstado] ?? 'Almacén';
        const badge = fila.querySelector('.rounded-full');
        if (badge) {
            const clsMap = {
                'Mantenimiento': 'bg-status-low/10 text-status-low',
                'Baja':          'bg-muted/10 text-muted',
                'Almacén':       'bg-status-free/10 text-status-free',
                'Asignado':      'bg-status-active/10 text-status-active',
            };
            badge.className = `px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${clsMap[estadoDisplay] ?? ''}`;
            badge.textContent = estadoDisplay;
        }
    }

    // Actualizar el select para reflejar el nuevo estado guardado
    if (nuevoEstado === 'almacen') {
        select.querySelector('option[value=""]').textContent = 'Automático (Almacén)';
        select.value = '';
    }
}

function filtrarResguardos() {
    const tipo   = document.getElementById('f-rsg-tipo').value;
    const estado = document.getElementById('f-rsg-estado').value;
    const texto  = document.getElementById('f-rsg-texto').value.toLowerCase();
    let vis = 0;
    document.querySelectorAll('.rsg-row').forEach(row => {
        const ok = (!tipo   || row.dataset.tipo   === tipo)
                && (!estado || row.dataset.estado  === estado)
                && (!texto  || row.dataset.texto.includes(texto));
        row.classList.toggle('hidden', !ok);
        if (ok) vis++;
    });
    const count = document.getElementById('f-rsg-count');
    if (count) count.textContent = `${vis} resultado${vis !== 1 ? 's' : ''}`;
}

// ── Panel lateral: detalle de equipo ─────────────────────────────────
let panelEquipoId = null;
let panelEquipoUserId = null;

const ESTADO_BADGE_CLASSES = {
    'Asignado':      'bg-status-active/10 text-status-active',
    'Almacén':       'bg-status-free/10 text-status-free',
    'Mantenimiento': 'bg-status-low/10 text-status-low',
    'Baja':          'bg-muted/10 text-muted',
};

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
    panelEquipoUserId = eq.user_id;

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
        : eq.user_id ? 'Asignado'
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
        </div>`;

    if (eq.tipo === 'Telefono') {
        // Sección específica de teléfono
        const ipHtml = (eq.ipv4_real || eq.ipv4)
            ? `<a href="http://${eq.ipv4_real || eq.ipv4}" target="_blank" rel="noopener"
                  class="font-mono text-brand hover:underline flex items-center gap-1">
                   ${eq.ipv4_real || eq.ipv4}
                   <span class="material-symbols-outlined" style="font-size:15px">open_in_new</span>
               </a>`
            : '<span class="text-muted text-sm">Sin IP asignada</span>';

        html += `<div class="bg-[#F9FAFB] border border-border rounded-xl p-4">
            <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-3">Teléfono</p>
            <div class="grid grid-cols-2 gap-3">
                ${f('Extensión', eq.extension, true)}
                ${f('Número general', eq.numero_general, true)}
                ${eq.cpu_marca ? f('Marca', eq.cpu_marca) : ''}
                ${eq.cpu_modelo ? f('Modelo', eq.cpu_modelo) : ''}
                ${eq.cpu_serie  ? f('No. Serie', eq.cpu_serie, true) : ''}
            </div>
            <div class="mt-3 detail-field">
                <span class="detail-label">Dirección IP</span>
                <div class="mt-0.5">${ipHtml}</div>
            </div>
        </div>`;
    } else {
        html += `
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
    }

    // Responsable + Usuario actual + Estado
    const usuarioActualLabel = eq.usuario_nombre && eq.usuario_nombre !== eq.empleado_nombre
        ? `${eq.usuario_nombre}${eq.usuario_correo ? ' &lt;' + eq.usuario_correo + '&gt;' : ''}`
        : (eq.usuario_nombre ? `${eq.usuario_nombre}${eq.usuario_correo ? ' &lt;' + eq.usuario_correo + '&gt;' : ''}` : 'Sin usuario asignado');

    html += `<div class="bg-[#F9FAFB] border border-border rounded-xl p-4">
        <p class="text-[10px] font-bold uppercase tracking-wider text-muted mb-3">Responsable / Estado</p>
        <div class="grid grid-cols-1 gap-3">
            ${f('Responsable', eq.empleado_nombre ? `${eq.empleado_nombre}${eq.empleado_correo ? ' &lt;' + eq.empleado_correo + '&gt;' : ''}` : 'Sin vincular')}
            <div class="detail-field">
                <span class="detail-label">Usuario actual <span class="text-[9px] text-muted normal-case tracking-normal">(quien lo usa físicamente)</span></span>
                <div class="flex items-center gap-2 mt-1" id="usuario-display-wrap-${eq.id}">
                    <span class="text-sm flex-1" id="usuario-display-${eq.id}">${usuarioActualLabel}</span>
                    <button onclick="toggleCambioUsuario(${eq.id})"
                            class="text-xs text-brand hover:underline shrink-0">Cambiar</button>
                </div>
                <div class="hidden mt-2 flex items-center gap-2" id="usuario-edit-wrap-${eq.id}">
                    <select id="select-usuario-${eq.id}"
                            class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand flex-1">
                        <option value="">— Sin usuario —</option>
                        @foreach(\DB::table('users')->where('activo', 1)->orderBy('name')->select('id', 'name', 'apellido_paterno')->get() as $u)
                        <option value="{{ $u->id }}" ${eq.usuario_actual_id == {{ $u->id }} ? 'selected' : ''}>
                            {{ trim($u->name . ' ' . $u->apellido_paterno) }}
                        </option>
                        @endforeach
                    </select>
                    <button onclick="guardarUsuario(${eq.id})"
                            class="px-3 py-1.5 bg-brand text-white text-xs font-bold rounded hover:opacity-90 shrink-0">
                        Guardar
                    </button>
                </div>
            </div>
            <div class="detail-field">
                <span class="detail-label">Estado del equipo</span>
                <div class="flex items-center gap-2 mt-1">
                    <select id="select-estado-panel" class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand"
                            onchange="">
                        <option value="" ${!eq.estado ? 'selected' : ''}>Automático (${estadoDisplay})</option>
                        <option value="almacen">Almacén (desvincula responsable)</option>
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

    // Cargar historial de movimientos al final del panel
    fetch(`/kardex/equipo/${id}/historial`)
        .then(r => r.json())
        .then(movs => {
            const cuerpo = document.getElementById('panel-cuerpo');
            if (cuerpo) cuerpo.insertAdjacentHTML('beforeend', renderHistorial(movs));
        });
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
        // "almacen" desvincula al responsable → el estado real pasa a
        // Almacén independientemente de qué tuviera panelEquipoUserId antes.
        if (nuevoEstado === 'almacen') panelEquipoUserId = null;

        const labelMap = { mantenimiento: 'Mantenimiento', baja: 'Baja', almacen: 'Almacén' };
        const estadoDisplay = labelMap[nuevoEstado] ?? (panelEquipoUserId ? 'Asignado' : 'Almacén');
        const badgeClass = ESTADO_BADGE_CLASSES[estadoDisplay] ?? '';

        // Actualizar badge + dataset en ambas tablas (Equipos y Resguardos)
        ['estado-badge-' + id, 'estado-badge-rsg-' + id].forEach(badgeId => {
            const badge = document.getElementById(badgeId);
            if (badge) {
                badge.textContent = estadoDisplay;
                badge.className = 'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ' + badgeClass;
            }
        });
        document.querySelectorAll(`.eq-row[data-id="${id}"], .rsg-row[data-id="${id}"]`).forEach(row => {
            row.dataset.estado = estadoDisplay;
        });

        if (nuevoEstado === 'almacen') {
            ['responsable-cell-' + id, 'responsable-cell-rsg-' + id].forEach(cellId => {
                const cell = document.getElementById(cellId);
                if (cell) { cell.textContent = '—'; cell.removeAttribute('title'); }
            });
        }

        // El estado dejó de ser "Asignado" (o cambió) → su IP pudo liberarse
        // en el servidor, o se desvinculó al responsable; refrescamos el
        // panel para reflejar el estado real que quedó en la BD.
        if (nuevoEstado === 'mantenimiento' || nuevoEstado === 'baja' || nuevoEstado === 'almacen') {
            abrirPanelEquipo(id);
        }

        select.closest('.detail-field')?.querySelector('button')?.classList.add('opacity-50');
        setTimeout(() => select.closest('.detail-field')?.querySelector('button')?.classList.remove('opacity-50'), 1000);
    }
}

function toggleCambioUsuario(id) {
    document.getElementById('usuario-display-wrap-' + id)?.classList.toggle('hidden');
    document.getElementById('usuario-edit-wrap-'   + id)?.classList.toggle('hidden');
}

async function guardarUsuario(id) {
    const select    = document.getElementById('select-usuario-' + id);
    const usuarioId = select?.value || null;
    const r = await fetch(`/kardex/equipo/${id}/usuario`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: JSON.stringify({ usuario_id: usuarioId }),
    });
    if (!r.ok) return;
    const data = await r.json();

    // Actualizar el texto en el panel sin recargar todo
    const display = document.getElementById('usuario-display-' + id);
    if (display) display.textContent = data.usuario_nombre ?? 'Sin usuario asignado';

    toggleCambioUsuario(id);

    // Actualizar sub-línea en la fila de la tabla
    const fila = document.querySelector(`tr[data-id="${id}"]`);
    const cell = document.getElementById('responsable-cell-' + id);
    if (cell) {
        let subline = cell.querySelector('.usuario-subline');
        if (data.usuario_nombre) {
            if (!subline) {
                subline = document.createElement('p');
                subline.className = 'usuario-subline truncate text-xs text-muted mt-0.5';
                cell.appendChild(subline);
            }
            subline.innerHTML = `<span class="text-brand/60">↳</span> ${data.usuario_nombre}`;
        } else if (subline) {
            subline.remove();
        }
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
