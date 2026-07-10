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
                        data-area="{{ strtolower($ip->departamento_pestana ?? '') }}"
                        data-estatus="{{ strtolower($ip->estatus ?? 'libre') }}"
                        onclick="openIpPanel('{{ $ip->ip }}', '{{ addslashes($ip->usuario ?? '—') }}', '{{ addslashes($ip->area_excel ?? $ip->departamento_pestana ?? '—') }}')">
                        <td class="px-6 py-3 font-mono text-sm text-brand">{{ $ip->ip }}</td>
                        <td class="px-6 py-3 text-sm">{{ $ip->usuario ?: '—' }}</td>
                        <td class="px-6 py-3 text-sm text-muted">{{ $ip->tipo_equipo ?: '—' }}
                            @if($ip->marca) · {{ $ip->marca }} @endif
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
                    <p class="text-sm flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">settings_ethernet</span>
                        Ethernet
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
                <span class="text-[10px] bg-gold/20 text-brand px-2 py-0.5 font-bold rounded">Perfil: Estándar</span>
            </div>
            <div class="grid grid-cols-3 gap-3">
                @php $perms = [
                    ['icon'=>'account_balance', 'label'=>'Sitios Gov',  'ok'=>true],
                    ['icon'=>'newspaper',       'label'=>'Noticias',    'ok'=>true],
                    ['icon'=>'smart_display',   'label'=>'YouTube',     'ok'=>false],
                    ['icon'=>'share',           'label'=>'Social',      'ok'=>false],
                    ['icon'=>'mail',            'label'=>'Webmail',     'ok'=>true],
                    ['icon'=>'public',          'label'=>'Intranet',    'ok'=>true],
                ]; @endphp
                @foreach($perms as $p)
                <div class="p-3 bg-surface border border-border rounded flex flex-col items-center gap-1 text-center {{ !$p['ok'] ? 'grayscale opacity-60' : '' }}">
                    <span class="material-symbols-outlined {{ $p['ok'] ? 'text-status-active' : 'text-status-critical' }}">{{ $p['icon'] }}</span>
                    <span class="text-[10px] font-medium leading-tight">{{ $p['label'] }}</span>
                    <span class="material-symbols-outlined text-xs {{ $p['ok'] ? 'text-status-active' : 'text-status-critical' }}"
                          style="font-variation-settings:'FILL' 1">{{ $p['ok'] ? 'check_circle' : 'cancel' }}</span>
                </div>
                @endforeach
            </div>
        </section>

        {{-- Historial Reciente --}}
        <section>
            <h4 class="text-[11px] font-bold uppercase tracking-wider text-muted mb-4 pb-2 border-b border-border">Historial Reciente</h4>
            <div class="text-center text-muted py-6">
                <span class="material-symbols-outlined text-3xl block mb-2">history</span>
                <p class="text-sm">Sin historial disponible</p>
            </div>
        </section>
    </div>

    <div class="p-6 border-t border-border bg-wash flex gap-3">
        <button class="flex-1 py-3 bg-brand text-white font-bold rounded-lg text-sm hover:opacity-90 transition-opacity">Editar Configuración</button>
        <button class="flex-1 py-3 bg-canvas border border-gold text-brand font-bold rounded-lg text-sm hover:bg-gold/10 transition-colors">Liberar IP</button>
    </div>
</div>

{{-- Backdrop --}}
<div class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[55] hidden" id="ip-backdrop" onclick="closeIpPanel()"></div>

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

function openIpPanel(ip, user, area) {
    document.getElementById('panel-ip-addr').innerText = ip || '—';
    document.getElementById('panel-ip-user').innerText = user || '—';
    document.getElementById('panel-ip-area').innerText = area || '—';
    document.getElementById('ip-panel').classList.remove('closed');
    document.getElementById('ip-backdrop').classList.remove('hidden');
}

function closeIpPanel() {
    document.getElementById('ip-panel').classList.add('closed');
    document.getElementById('ip-backdrop').classList.add('hidden');
}

document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeIpPanel(); });

function filterIpTable() {
    const area = document.getElementById('net-filter-area').value.toLowerCase();
    const estatus = document.getElementById('net-filter-estatus')?.value.toLowerCase() || '';
    document.querySelectorAll('#ip-tbody tr').forEach(row => {
        const rowArea  = (row.dataset.area || '').toLowerCase();
        const rowStat  = (row.dataset.estatus || '').toLowerCase();
        const areaOk   = !area   || rowArea.includes(area);
        const statOk   = !estatus || rowStat.includes(estatus);
        row.style.display = (areaOk && statOk) ? '' : 'none';
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
