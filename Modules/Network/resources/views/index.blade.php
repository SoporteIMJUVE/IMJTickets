<x-layouts.app title="Red e IPs — IMJUVE CRM">
<div class="p-8" id="network-page">

{{-- Header + toggle de vista --}}
    {{-- Header + toggle de vista --}}
    <div class="flex justify-between items-end mb-6">
        <div>
            <h2 class="text-[32px] font-bold leading-10 tracking-tight text-brand">Gestión de Red e IPs</h2>
            <p class="text-muted text-sm mt-1">Monitoreo y administración de infraestructura de red institucional.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex bg-wash rounded-lg p-1">
                <button id="nav-rangos" onclick="switchNetTab('rangos')"
                        class="px-4 py-2 rounded-md text-sm font-bold transition-all bg-canvas text-brand shadow-sm">
                    Rangos y Disponibilidad
                </button>
                <button id="nav-inventario" onclick="switchNetTab('inventario')"
                        class="px-4 py-2 rounded-md text-sm font-bold transition-all text-muted hover:bg-surface-high">
                    Inventario de IPs
                </button>
            </div>
            <button onclick="alert('Ajustes del módulo aún no disponibles.')"
                    class="p-2 border border-border rounded-lg hover:bg-wash transition-colors text-muted"
                    title="Ajustes">
                <span class="material-symbols-outlined text-sm">settings</span>
            </button>
        </div>
    </div>

    {{-- ---- VIEW: RANGOS ---- --}}
    <div id="view-rangos">
        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-6 mb-8">
            <div class="bg-canvas p-6 rounded-xl border border-border shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2">IPs Totales</p>
                <h3 class="text-3xl font-bold text-brand">{{ $totalIps }}</h3>
                <div class="mt-4 flex items-center gap-2 text-status-active text-xs">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    Sistema Operativo
                </div>
            </div>
            <div class="bg-canvas p-6 rounded-xl border border-border shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2">IPs En Uso</p>
                <h3 class="text-3xl font-bold text-brand">{{ $ipsEnUso }}</h3>
                @php $pct = $totalIps > 0 ? round($ipsEnUso / $totalIps * 100) : 0; @endphp
                <div class="mt-4 h-2 bg-wash rounded-full overflow-hidden">
                    <div class="h-full bg-gold rounded-full" style="width:{{ $pct }}%"></div>
                </div>
            </div>
            <div class="bg-canvas p-6 rounded-xl border border-border shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Alertas de Saturación</p>
                <h3 class="text-3xl font-bold text-status-critical">{{ $alertas }}</h3>
                <div class="mt-4 flex items-center gap-2 text-status-critical text-xs">
                    <span class="material-symbols-outlined text-sm">warning</span>
                    Rangos con &gt;90%
                </div>
            </div>
        </div>

        {{-- Ranges Table --}}
        <div class="bg-canvas rounded-xl border border-border shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-wash border-b border-border">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted">Área Institucional</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted">Rango de IPs</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted">Uso de Capacidad</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted">Estado</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($rangos as $rango)
                    @php
                        $cap  = $rango->capacidad_total ?: 1;
                        $pctR = round(($rango->ocupadas_real / $cap) * 100);
                        $color = $pctR >= 90 ? 'var(--color-status-critical)' : ($pctR >= 70 ? 'var(--color-status-low)' : 'var(--color-status-active)');
                    @endphp
                    <tr class="hover:bg-gold/5 transition-colors">
                        <td class="px-6 py-4 text-sm font-medium">{{ $rango->area_nombre }}</td>
                        <td class="px-6 py-4 font-mono text-sm text-muted">
                            {{ $rango->ip_inicial }} – {{ $rango->ip_final }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-wash rounded-full overflow-hidden">
                                    <div class="h-full rounded-full" style="width:{{ $pctR }}%; background:{{ $color }}"></div>
                                </div>
                                <span class="font-mono text-xs text-muted">{{ $rango->ocupadas_real }}/{{ $rango->capacidad_total }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($pctR >= 90)
                            <span class="bg-status-critical/10 text-status-critical px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Saturado</span>
                            @elseif($pctR >= 70)
                            <span class="bg-status-low/10 text-status-low px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Lleno</span>
                            @else
                            <span class="bg-status-active/10 text-status-active px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Disponible</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <button onclick="switchNetTab('inventario')"
                                    class="px-3 py-1 border border-gold rounded text-brand text-xs font-bold hover:bg-surface-high">
                                Ver IPs
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-muted text-sm">Sin rangos configurados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ---- VIEW: INVENTARIO IPs ---- --}}
    <div id="view-inventario" class="hidden">
        <div class="bg-canvas border border-border rounded-xl overflow-hidden shadow-sm mb-6">
            <x-tabla-encabezado titulo="Inventario de IPs" tab="ips" exportUrl="{{ route('network.exportar') }}">
                <x-slot:filtros>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Usuario o IP</label>
                        <input id="net-filter-texto" oninput="filterIpTable()" type="text"
                               placeholder="Nombre asignado o IP..."
                               class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand w-56">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Estado</label>
                        <select id="net-filter-estatus" onchange="filterIpTable()"
                                class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand">
                            <option value="">Todos</option>
                            <option value="ocupada">Ocupada</option>
                            <option value="libre">Libre</option>
                            <option value="reservada">Reservada</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-muted mb-1">Área</label>
                        <select id="net-filter-area" onchange="filterIpTable()"
                                class="text-sm border border-border rounded px-3 py-1.5 bg-canvas outline-none focus:ring-2 focus:ring-brand">
                            <option value="">Todas</option>
                            @foreach($rangos as $r)
                            <option value="{{ strtolower($r->siglas_real ?? '') }}">{{ $r->siglas_real ?: $r->area_nombre }} — {{ Str::limit($r->area_nombre, 35) }}</option>
                            @endforeach
                        </select>
                    </div>
                </x-slot:filtros>
            </x-tabla-encabezado>
        </div>

        <div class="bg-canvas rounded-xl border border-border shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-wash border-b border-border">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted">IP Address</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted">Usuario Asignado</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted">Dispositivo</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted">Estado</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-muted text-right">Detalles</th>
                    </tr>
                </thead>
                <tbody id="ip-tbody" class="divide-y divide-border">
                    @forelse($ipsAll as $ip)
                    <tr class="hover:bg-gold/5 transition-colors cursor-pointer"
                        data-ip="{{ $ip->ip }}"
                        data-usuario="{{ strtolower(($ip->responsable_fk ?? $ip->usuario) ?? '') }}"
                        data-area="{{ strtolower($ip->departamento_pestana ?? '') }}"
                        data-estatus="{{ strtolower($ip->estatus ?? 'libre') }}"
                        onclick="abrirPanelIp({{ $ip->id }})">
                        <td class="px-6 py-3 font-mono text-sm text-brand">{{ $ip->ip }}</td>
                        <td class="px-6 py-3 text-sm">
                            {{ ($ip->responsable_fk ?? $ip->usuario) ?: '—' }}
                            @if($ip->activo_serie)
                            <br><span class="font-mono text-[11px] text-muted">{{ $ip->activo_serie }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-sm text-muted">{{ $ip->tipo_display ?: '—' }}
                            @if($ip->marca_display) · {{ $ip->marca_display }} @endif
                        </td>
                        <td class="px-6 py-3">
                            @if(strtolower($ip->estatus ?? '') === 'ocupada')
                            <span class="bg-status-active/10 text-status-active px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Ocupada</span>
                            @elseif(strtolower($ip->estatus ?? '') === 'reservada')
                            <span class="bg-status-low/10 text-status-low px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Reservada</span>
                            @else
                            <span class="bg-status-free/10 text-status-free px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Libre</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            <button class="p-1.5 hover:bg-wash rounded text-muted hover:text-brand">
                                <span class="material-symbols-outlined text-sm">info</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-muted text-sm">Sin IPs registradas</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Side Panel: IP Detail --}}
<div class="fixed top-0 right-0 h-screen w-[400px] bg-canvas shadow-2xl border-l border-gold z-[60] detail-panel closed flex flex-col" id="ip-panel">
    <div class="p-6 border-b border-border bg-surface flex justify-between items-center">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Detalles de Dispositivo</p>
            <h2 class="font-mono text-xl font-bold text-brand" id="panel-ip-addr">—.—.—.—</h2>
        </div>
        <button class="w-10 h-10 rounded-full hover:bg-wash flex items-center justify-center transition-colors" onclick="closeIpPanel()">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-8 custom-scrollbar">
        {{-- Hardware Specs --}}
        <section>
            <h4 class="text-[11px] font-bold uppercase tracking-wider text-muted mb-4 pb-2 border-b border-border">
                Información Técnica
            </h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="space-y-1">
                    <p class="text-[10px] text-muted font-medium">MAC Address</p>
                    <p class="font-mono text-sm" id="panel-mac">—</p>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] text-muted font-medium">Tipo Conexión</p>
                    <p class="text-sm flex items-center gap-1" id="panel-tipo-conexion">
                        <span class="material-symbols-outlined text-sm">help</span>
                        —
                    </p>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] text-muted font-medium">Usuario</p>
                    <p class="text-sm font-bold" id="panel-ip-user">—</p>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] text-muted font-medium">Área</p>
                    <p class="text-sm" id="panel-ip-area">—</p>
                </div>
            </div>
        </section>

        {{-- Permisos de Navegación --}}
        <section>
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-border">
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-muted">Permisos de Navegación</h4>
            </div>
            <div class="grid grid-cols-3 gap-3" id="panel-permisos-grid">
                <p class="col-span-3 text-xs text-muted italic">Cargando…</p>
            </div>
        </section>

        {{-- Historial Reciente --}}
        <section>
            <h4 class="text-[11px] font-bold uppercase tracking-wider text-muted mb-4 pb-2 border-b border-border">Historial Reciente</h4>
            <div id="panel-historial">
                <div class="text-center text-muted py-6">
                    <span class="material-symbols-outlined text-3xl block mb-2 animate-pulse">history</span>
                    <p class="text-sm">Cargando…</p>
                </div>
            </div>
        </section>
    </div>

    <div class="p-6 border-t border-border bg-wash flex gap-3">
        <button onclick="abrirModalConfig()"
                class="flex-1 py-3 bg-brand text-white font-bold rounded-lg text-sm hover:opacity-90 transition-opacity">Editar Configuración</button>
        <button id="btn-liberar-ip" onclick="abrirModalLiberar()"
                class="flex-1 py-3 bg-canvas border border-gold text-brand font-bold rounded-lg text-sm hover:bg-gold/10 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">Liberar IP</button>
    </div>
</div>

{{-- Backdrop --}}
<div class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[55] hidden" id="ip-backdrop" onclick="closeIpPanel()"></div>

{{-- Modal: Editar Configuración --}}
<div id="modal-config" class="fixed inset-0 z-[70] hidden items-center justify-center" style="background:rgba(0,0,0,.35)">
    <div class="bg-canvas rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden max-h-[90vh] flex flex-col">
        <div class="px-6 py-5 border-b border-border flex items-center justify-between">
            <h3 class="font-bold text-base text-ink">Editar configuración — <span id="config-ip-label" class="font-mono"></span></h3>
        </div>
        <div class="px-6 py-5 space-y-4 overflow-y-auto">
            <div id="config-errores" class="hidden rounded-lg px-4 py-3 text-sm font-semibold" style="background:var(--color-error-container);color:var(--color-error)"></div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">MAC Address</label>
                <input type="text" id="config-mac"
                       class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-brand font-mono">
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Tipo de conexión</label>
                <div class="flex gap-3">
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="radio" name="config-tipo-conexion" value="ALÁMBRICO"> Ethernet
                    </label>
                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input type="radio" name="config-tipo-conexion" value="INALÁMBRICO"> WiFi
                    </label>
                </div>
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-2">Permisos de navegación</label>
                <div class="grid grid-cols-2 gap-2" id="config-permisos-grid"></div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-border flex justify-end gap-3">
            <button type="button" onclick="cerrarModal('modal-config')"
                    class="px-4 py-2 border border-border rounded-lg text-sm font-bold text-muted hover:bg-wash transition-colors">Cancelar</button>
            <button type="button" onclick="guardarConfig()"
                    class="px-4 py-2 bg-brand text-white rounded-lg text-sm font-bold hover:opacity-90 transition-colors">Guardar</button>
        </div>
    </div>
</div>

{{-- Modal: Liberar IP --}}
<div id="modal-liberar" class="fixed inset-0 z-[70] hidden items-center justify-center" style="background:rgba(0,0,0,.35)">
    <div class="bg-canvas rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden">
        <div class="px-6 py-5 border-b border-border">
            <h3 class="font-bold text-base text-ink">Liberar IP — <span id="liberar-ip-label" class="font-mono"></span></h3>
            <p class="text-xs text-muted mt-1">Ocupada actualmente por: <span id="liberar-ocupante" class="font-semibold"></span></p>
        </div>
        <div class="px-6 py-5 space-y-4">
            <div id="liberar-errores" class="hidden rounded-lg px-4 py-3 text-sm font-semibold" style="background:var(--color-error-container);color:var(--color-error)"></div>

            <label class="flex items-start gap-2 p-2 rounded-lg hover:bg-surface-low cursor-pointer">
                <input type="radio" name="liberar-modo" value="estado" checked onchange="cambiarModoLiberar()" class="mt-0.5">
                <div>
                    <p class="text-sm font-semibold text-ink">Dar de baja / mantenimiento al equipo actual</p>
                    <p class="text-[11px] text-muted">El equipo se queda sin IP.</p>
                </div>
            </label>
            <div id="liberar-sub-estado" class="pl-6">
                <select id="liberar-estado-select"
                        class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-brand">
                    <option value="mantenimiento">Mantenimiento</option>
                    <option value="baja">Baja</option>
                </select>
            </div>

            <label class="flex items-start gap-2 p-2 rounded-lg hover:bg-surface-low cursor-pointer">
                <input type="radio" name="liberar-modo" value="switch" onchange="cambiarModoLiberar()" class="mt-0.5">
                <div>
                    <p class="text-sm font-semibold text-ink">Cambiar esta IP a otro equipo</p>
                    <p class="text-[11px] text-muted">Switcheo — igual que en el formulario de resguardo.</p>
                </div>
            </label>
            <div id="liberar-sub-switch" class="pl-6 hidden">
                <select id="liberar-equipo-select"
                        class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-brand"></select>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-border flex justify-end gap-3">
            <button type="button" onclick="cerrarModal('modal-liberar')"
                    class="px-4 py-2 border border-border rounded-lg text-sm font-bold text-muted hover:bg-wash transition-colors">Cancelar</button>
            <button type="button" onclick="confirmarLiberar()"
                    class="px-4 py-2 bg-brand text-white rounded-lg text-sm font-bold hover:opacity-90 transition-colors">Confirmar</button>
        </div>
    </div>
</div>

<script>
function switchNetTab(tab) {
    const isRangos = tab === 'rangos';
    document.getElementById('view-rangos').classList.toggle('hidden', !isRangos);
    document.getElementById('view-inventario').classList.toggle('hidden', isRangos);

    const activeClass = 'px-4 py-2 rounded-md text-sm font-bold transition-all bg-canvas text-brand shadow-sm';
    const inactiveClass = 'px-4 py-2 rounded-md text-sm font-bold transition-all text-muted hover:bg-surface-high';
    document.getElementById('nav-rangos').className = isRangos ? activeClass : inactiveClass;
    document.getElementById('nav-inventario').className = !isRangos ? activeClass : inactiveClass;
}

const PERMISOS_INFO = {
    youtube:          { icon: 'smart_display', label: 'YouTube' },
    vimeo:            { icon: 'movie',          label: 'Vimeo' },
    spotify:          { icon: 'music_note',     label: 'Spotify' },
    otros_streaming:  { icon: 'live_tv',        label: 'Otro streaming' },
    facebook:         { icon: 'thumb_up',       label: 'Facebook' },
    tiktok:           { icon: 'music_video',    label: 'TikTok' },
    instagram:        { icon: 'photo_camera',   label: 'Instagram' },
    whatsapp_web:     { icon: 'chat',           label: 'WhatsApp Web' },
    otra_red_social:  { icon: 'share',          label: 'Otra red social' },
    sitios_gub:       { icon: 'account_balance',label: 'Sitios Gob' },
    noticias:         { icon: 'newspaper',      label: 'Noticias' },
    otro_permiso:     { icon: 'more_horiz',     label: 'Otro' },
};

let ipActual = null; // último detalle cargado, lo usan los modales

function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]').content;
}

async function abrirPanelIp(id) {
    document.getElementById('ip-panel').classList.remove('closed');
    document.getElementById('ip-backdrop').classList.remove('hidden');

    const resp = await fetch(`{{ url('/network/ip') }}/${id}/detalle`);
    if (!resp.ok) return;
    const data = await resp.json();
    ipActual = data;

    document.getElementById('panel-ip-addr').innerText = data.ip || '—';
    document.getElementById('panel-ip-user').innerText = data.usuario || '—';
    document.getElementById('panel-ip-area').innerText = data.area || '—';
    document.getElementById('panel-mac').innerText = data.mac || '—';

    const esWifi = data.tipo_conexion === 'INALÁMBRICO';
    document.getElementById('panel-tipo-conexion').innerHTML =
        `<span class="material-symbols-outlined text-sm">${esWifi ? 'wifi' : 'settings_ethernet'}</span> ${esWifi ? 'WiFi' : 'Ethernet'}`;

    const grid = document.getElementById('panel-permisos-grid');
    grid.innerHTML = '';
    for (const campo in PERMISOS_INFO) {
        const ok = !!data.permisos[campo];
        const info = PERMISOS_INFO[campo];
        grid.innerHTML += `
            <div class="p-3 bg-surface border border-border rounded flex flex-col items-center gap-1 text-center ${!ok ? 'grayscale opacity-60' : ''}">
                <span class="material-symbols-outlined ${ok ? 'text-status-active' : 'text-status-critical'}">${info.icon}</span>
                <span class="text-[10px] font-medium leading-tight">${info.label}</span>
                <span class="material-symbols-outlined text-xs ${ok ? 'text-status-active' : 'text-status-critical'}"
                      style="font-variation-settings:'FILL' 1">${ok ? 'check_circle' : 'cancel'}</span>
            </div>`;
    }

    const btnLiberar = document.getElementById('btn-liberar-ip');
    btnLiberar.disabled = data.estatus !== 'Ocupada';

    // Cargar historial Kardex del dispositivo que ocupa esta IP
    cargarHistorialIp(data.ocupante);
}

const _KARDEX_BASE = '{{ url('/kardex') }}';

const _EVENTO_ESTILOS = {
    'Entrada':       { dot: 'bg-green-500',  txt: 'text-green-700'  },
    'Asignación':    { dot: 'bg-green-500',  txt: 'text-green-700'  },
    'Asignación IP': { dot: 'bg-blue-500',   txt: 'text-blue-700'   },
    'Reasignación':  { dot: 'bg-amber-500',  txt: 'text-amber-700'  },
    'Cambio IP':     { dot: 'bg-amber-500',  txt: 'text-amber-700'  },
    'Liberación IP': { dot: 'bg-slate-400',  txt: 'text-slate-600'  },
    'Almacén':       { dot: 'bg-slate-400',  txt: 'text-slate-600'  },
    'Mantenimiento': { dot: 'bg-orange-500', txt: 'text-orange-700' },
    'Baja':          { dot: 'bg-red-500',    txt: 'text-red-700'    },
    'Reingreso':     { dot: 'bg-teal-500',   txt: 'text-teal-700'   },
};

async function cargarHistorialIp(ocupante) {
    const contenedor = document.getElementById('panel-historial');
    if (!ocupante) {
        contenedor.innerHTML = `<p class="text-xs text-muted italic text-center py-4">IP sin dispositivo asignado — sin historial.</p>`;
        return;
    }
    const tipo = ocupante.tabla === 'inventario_equipos' ? 'equipo' : 'impresora';
    const url  = `${_KARDEX_BASE}/${tipo}/${ocupante.id}/historial`;
    let movs;
    try {
        const r = await fetch(url);
        movs = r.ok ? await r.json() : [];
    } catch { movs = []; }

    if (!movs.length) {
        contenedor.innerHTML = `<p class="text-xs text-muted italic text-center py-4">Sin movimientos registrados para este dispositivo.</p>`;
        return;
    }

    // Solo los 10 más recientes en el panel lateral
    const recientes = movs.slice(0, 10);
    contenedor.innerHTML = `<div class="relative pl-4 border-l-2 border-border space-y-4">
        ${recientes.map(m => {
            const estilo  = _EVENTO_ESTILOS[m.tipo_evento] ?? { dot: 'bg-muted', txt: 'text-muted' };
            const fecha   = m.created_at ? new Date(m.created_at).toLocaleDateString('es-MX', { day:'2-digit', month:'short', year:'numeric' }) : '—';
            const deOrigen = m.origen ? `<span class="opacity-70">${m.origen}</span>` : '';
            const aDest    = m.destino ? ` → <span class="opacity-70">${m.destino}</span>` : '';
            const notas    = m.notas   ? `<p class="text-[10px] text-muted mt-0.5">${m.notas}</p>` : '';
            const por      = m.registrado_email ? `<p class="text-[10px] text-muted mt-0.5">por ${m.registrado_email}</p>` : '';
            return `<div class="relative">
                <span class="absolute -left-5 top-1 w-2.5 h-2.5 rounded-full ${estilo.dot} ring-2 ring-canvas"></span>
                <p class="text-[10px] text-muted leading-none mb-0.5">${fecha}</p>
                <p class="text-xs font-bold ${estilo.txt}">${m.tipo_evento}</p>
                ${(m.origen || m.destino) ? `<p class="text-[11px] text-on-surface">${deOrigen}${aDest}</p>` : ''}
                ${notas}${por}
            </div>`;
        }).join('')}
    </div>
    ${movs.length > 10 ? `<p class="text-[10px] text-muted text-center mt-3">+${movs.length - 10} movimientos más — ver en Kardex.</p>` : ''}`;
}

function closeIpPanel() {
    document.getElementById('ip-panel').classList.add('closed');
    document.getElementById('ip-backdrop').classList.add('hidden');
}

document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeIpPanel(); });

function cerrarModal(idModal) {
    document.getElementById(idModal).classList.add('hidden');
    document.getElementById(idModal).classList.remove('flex');
}

function abrirModal(idModal) {
    document.getElementById(idModal).classList.remove('hidden');
    document.getElementById(idModal).classList.add('flex');
}

// ── Modal: Editar Configuración ─────────────────────────────────────────
function abrirModalConfig() {
    if (!ipActual) return;
    document.getElementById('config-ip-label').innerText = ipActual.ip;
    document.getElementById('config-mac').value = ipActual.mac || '';
    document.getElementById('config-errores').classList.add('hidden');

    document.querySelectorAll('input[name="config-tipo-conexion"]').forEach(r => {
        r.checked = r.value === (ipActual.tipo_conexion || 'ALÁMBRICO');
    });

    const grid = document.getElementById('config-permisos-grid');
    grid.innerHTML = '';
    for (const campo in PERMISOS_INFO) {
        const checked = ipActual.permisos[campo] ? 'checked' : '';
        grid.innerHTML += `
            <label class="flex items-center gap-2 text-sm cursor-pointer">
                <input type="checkbox" value="${campo}" class="config-permiso-check accent-brand" ${checked}>
                ${PERMISOS_INFO[campo].label}
            </label>`;
    }

    abrirModal('modal-config');
}

async function guardarConfig() {
    const tipoConexion = document.querySelector('input[name="config-tipo-conexion"]:checked')?.value;
    const permisos = Array.from(document.querySelectorAll('.config-permiso-check:checked')).map(el => el.value);

    const resp = await fetch(`{{ url('/network/ip') }}/${ipActual.id}/configuracion`, {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: JSON.stringify({ mac: document.getElementById('config-mac').value, tipo_conexion: tipoConexion, permisos }),
    });
    const data = await resp.json();

    if (!resp.ok) {
        const box = document.getElementById('config-errores');
        box.textContent = Object.values(data.errors ?? {}).flat().join(' ') || 'Ocurrió un error.';
        box.classList.remove('hidden');
        return;
    }

    cerrarModal('modal-config');
    abrirPanelIp(ipActual.id);
}

// ── Modal: Liberar IP ────────────────────────────────────────────────────
function abrirModalLiberar() {
    if (!ipActual || ipActual.estatus !== 'Ocupada') return;
    document.getElementById('liberar-ip-label').innerText = ipActual.ip;
    document.getElementById('liberar-ocupante').innerText =
        `${ipActual.ocupante?.descripcion ?? '—'}${ipActual.ocupante?.responsable ? ' · ' + ipActual.ocupante.responsable : ''}`;
    document.getElementById('liberar-errores').classList.add('hidden');

    const select = document.getElementById('liberar-equipo-select');
    select.innerHTML = ipActual.equipos.map(eq =>
        `<option value="${eq.id}">${eq.tipo} — ${eq.cpu_serie || 'sin serie'}${eq.nombre_usuario ? ' — ' + eq.nombre_usuario : ''}</option>`
    ).join('');

    document.querySelector('input[name="liberar-modo"][value="estado"]').checked = true;
    cambiarModoLiberar();
    abrirModal('modal-liberar');
}

function cambiarModoLiberar() {
    const modo = document.querySelector('input[name="liberar-modo"]:checked').value;
    document.getElementById('liberar-sub-estado').classList.toggle('hidden', modo !== 'estado');
    document.getElementById('liberar-sub-switch').classList.toggle('hidden', modo !== 'switch');
}

async function confirmarLiberar() {
    const modo = document.querySelector('input[name="liberar-modo"]:checked').value;
    const url = modo === 'estado'
        ? `{{ url('/network/ip') }}/${ipActual.id}/liberar-estado`
        : `{{ url('/network/ip') }}/${ipActual.id}/liberar-switch`;
    const body = modo === 'estado'
        ? { estado: document.getElementById('liberar-estado-select').value }
        : { equipo_id: document.getElementById('liberar-equipo-select').value };

    const resp = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken() },
        body: JSON.stringify(body),
    });
    const data = await resp.json();

    if (!resp.ok) {
        const box = document.getElementById('liberar-errores');
        box.textContent = Object.values(data.errors ?? {}).flat().join(' ') || 'Ocurrió un error.';
        box.classList.remove('hidden');
        return;
    }

    cerrarModal('modal-liberar');
    closeIpPanel();
    window.location.reload();
}

function filterIpTable() {
    const area = document.getElementById('net-filter-area').value.toLowerCase();
    const estatus = document.getElementById('net-filter-estatus')?.value.toLowerCase() || '';
    const texto = (document.getElementById('net-filter-texto')?.value || '').trim().toLowerCase();
    document.querySelectorAll('#ip-tbody tr').forEach(row => {
        const rowArea    = (row.dataset.area || '').toLowerCase();
        const rowStat    = (row.dataset.estatus || '').toLowerCase();
        const rowIp      = (row.dataset.ip || '').toLowerCase();
        const rowUsuario = (row.dataset.usuario || '').toLowerCase();
        const areaOk   = !area   || rowArea.includes(area);
        const statOk   = !estatus || rowStat.includes(estatus);
        const textoOk  = !texto || rowIp.includes(texto) || rowUsuario.includes(texto);
        row.style.display = (areaOk && statOk && textoOk) ? '' : 'none';
    });
}

// Deep-link: ?open=ip abre el panel de esa IP directamente
(function () {
    const ip = new URLSearchParams(location.search).get('open');
    if (!ip) return;
    const row = document.querySelector(`tr[data-ip="${ip}"]`);
    if (row) { row.scrollIntoView({ block: 'center' }); row.click(); }
})();
</script>
</x-layouts.app>
