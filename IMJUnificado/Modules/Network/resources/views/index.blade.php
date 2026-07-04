<x-layouts.app title="Red e IPs — IMJUVE CRM">
<div class="p-8" id="network-page">

    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-[32px] font-bold leading-10 tracking-tight text-[#621132]">Gestión de Red e IPs</h2>
        <p class="text-[#544246] text-sm mt-1">Monitoreo y administración de infraestructura de red institucional.</p>
    </div>

    {{-- Tabs: Rangos / Inventario --}}
    <div class="flex items-center border-b border-[#E5E7EB] mb-6">
        <button id="nav-rangos" onclick="switchNetTab('rangos')"
                class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider border-b-2 border-[#621132] text-[#621132] transition-all">
            Rangos y Disponibilidad
        </button>
        <button id="nav-inventario" onclick="switchNetTab('inventario')"
                class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-[#544246] hover:text-[#621132] transition-all">
            Inventario de IPs
        </button>
    </div>

    {{-- ---- VIEW: RANGOS ---- --}}
    <div id="view-rangos">
        {{-- Stats --}}
        <div class="grid grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl border border-[#E5E7EB] shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-2">IPs Totales</p>
                <h3 class="text-3xl font-bold text-[#621132]">{{ $totalIps }}</h3>
                <div class="mt-4 flex items-center gap-2 text-[#166534] text-xs">
                    <span class="material-symbols-outlined text-sm">check_circle</span>
                    Sistema Operativo
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl border border-[#E5E7EB] shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-2">IPs En Uso</p>
                <h3 class="text-3xl font-bold text-[#621132]">{{ $ipsEnUso }}</h3>
                @php $pct = $totalIps > 0 ? round($ipsEnUso / $totalIps * 100) : 0; @endphp
                <div class="mt-4 h-2 bg-[#F3F4F6] rounded-full overflow-hidden">
                    <div class="h-full bg-[#D4C19C] rounded-full" style="width:{{ $pct }}%"></div>
                </div>
            </div>
            <div class="bg-white p-6 rounded-xl border border-[#E5E7EB] shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-2">Alertas de Saturación</p>
                <h3 class="text-3xl font-bold text-[#991B1B]">{{ $alertas }}</h3>
                <div class="mt-4 flex items-center gap-2 text-[#991B1B] text-xs">
                    <span class="material-symbols-outlined text-sm">warning</span>
                    Rangos con &gt;90%
                </div>
            </div>
        </div>

        {{-- Ranges Table --}}
        <div class="bg-white rounded-xl border border-[#E5E7EB] shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-[#F3F4F6] border-b border-[#E5E7EB]">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Área Institucional</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Rango de IPs</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Uso de Capacidad</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Estado</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E7EB]">
                    @forelse($rangos as $rango)
                    @php
                        $cap  = $rango->capacidad_total ?: 1;
                        $pctR = round(($rango->ocupadas / $cap) * 100);
                        $color = $pctR >= 90 ? '#991B1B' : ($pctR >= 70 ? '#9A3412' : '#166534');
                    @endphp
                    <tr class="hover:bg-[#D4C19C]/5 transition-colors">
                        <td class="px-6 py-4 text-sm font-medium">{{ $rango->area_nombre }}</td>
                        <td class="px-6 py-4 font-mono text-sm text-[#544246]">
                            {{ $rango->ip_inicial }} – {{ $rango->ip_final }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 h-2 bg-[#F3F4F6] rounded-full overflow-hidden">
                                    <div class="h-full rounded-full" style="width:{{ $pctR }}%; background:{{ $color }}"></div>
                                </div>
                                <span class="font-mono text-xs text-[#544246]">{{ $rango->ocupadas }}/{{ $rango->capacidad_total }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($pctR >= 90)
                            <span class="bg-[#991B1B]/10 text-[#991B1B] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Saturado</span>
                            @elseif($pctR >= 70)
                            <span class="bg-[#9A3412]/10 text-[#9A3412] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Lleno</span>
                            @else
                            <span class="bg-[#166534]/10 text-[#166534] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Disponible</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <button onclick="switchNetTab('inventario')"
                                    class="px-3 py-1 border border-[#D4C19C] rounded text-[#621132] text-xs font-bold hover:bg-[#eae8e7]">
                                Ver IPs
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-[#544246] text-sm">Sin rangos configurados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ---- VIEW: INVENTARIO IPs ---- --}}
    <div id="view-inventario" class="hidden">
        <div class="bg-white border border-[#E5E7EB] rounded-xl p-4 mb-6 flex justify-between items-center">
            <div class="flex gap-4">
                <select id="net-filter-estatus" onchange="filterIpTable()" class="text-xs font-bold border-[#E5E7EB] rounded-lg bg-[#fbf9f8] px-4 py-2 outline-none focus:ring-2 focus:ring-[#621132]">
                    <option value="">Todos los estados</option>
                    <option value="ocupada">Ocupada</option>
                    <option value="libre">Libre</option>
                    <option value="reservada">Reservada</option>
                </select>
                <select id="net-filter-area" onchange="filterIpTable()" class="text-xs font-bold border-[#E5E7EB] rounded-lg bg-[#fbf9f8] px-4 py-2 outline-none focus:ring-2 focus:ring-[#621132]">
                    <option value="">Todas las áreas</option>
                    @foreach($rangos as $r)
                    <option value="{{ $r->siglas }}">{{ $r->siglas }} — {{ Str::limit($r->area_nombre, 40) }}</option>
                    @endforeach
                </select>
            </div>
            <button class="bg-[#F3F4F6] text-[#544246] px-4 py-2 rounded-lg text-xs font-bold flex items-center gap-2 hover:bg-[#eae8e7] transition-colors">
                <span class="material-symbols-outlined text-sm">download</span> Exportar CSV
            </button>
        </div>

        <div class="bg-white rounded-xl border border-[#E5E7EB] shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-[#F3F4F6] border-b border-[#E5E7EB]">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">IP Address</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Usuario Asignado</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Dispositivo</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Estado</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246] text-right">Detalles</th>
                    </tr>
                </thead>
                <tbody id="ip-tbody" class="divide-y divide-[#E5E7EB]">
                    @forelse($ipsAll as $ip)
                    <tr class="hover:bg-[#D4C19C]/5 transition-colors cursor-pointer"
                        data-area="{{ strtolower($ip->departamento_pestana ?? '') }}"
                        data-estatus="{{ strtolower($ip->estatus ?? 'libre') }}"
                        onclick="openIpPanel('{{ $ip->ip }}', '{{ addslashes($ip->usuario ?? '—') }}', '{{ addslashes($ip->area_excel ?? $ip->departamento_pestana ?? '—') }}')">
                        <td class="px-6 py-3 font-mono text-sm text-[#621132]">{{ $ip->ip }}</td>
                        <td class="px-6 py-3 text-sm">{{ $ip->usuario ?: '—' }}</td>
                        <td class="px-6 py-3 text-sm text-[#544246]">{{ $ip->tipo_equipo ?: '—' }}
                            @if($ip->marca) · {{ $ip->marca }} @endif
                        </td>
                        <td class="px-6 py-3">
                            @if(strtolower($ip->estatus ?? '') === 'ocupada')
                            <span class="bg-[#166534]/10 text-[#166534] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Ocupada</span>
                            @elseif(strtolower($ip->estatus ?? '') === 'reservada')
                            <span class="bg-[#9A3412]/10 text-[#9A3412] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Reservada</span>
                            @else
                            <span class="bg-[#1E40AF]/10 text-[#1E40AF] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Libre</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            <button class="p-1.5 hover:bg-[#F3F4F6] rounded text-[#544246] hover:text-[#621132]">
                                <span class="material-symbols-outlined text-sm">info</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-[#544246] text-sm">Sin IPs registradas</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Side Panel: IP Detail --}}
<div class="fixed top-0 right-0 h-screen w-[400px] bg-white shadow-2xl border-l border-[#D4C19C] z-[60] detail-panel closed flex flex-col" id="ip-panel">
    <div class="p-6 border-b border-[#E5E7EB] bg-[#fbf9f8] flex justify-between items-center">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246]">Detalles de Dispositivo</p>
            <h2 class="font-mono text-xl font-bold text-[#621132]" id="panel-ip-addr">—.—.—.—</h2>
        </div>
        <button class="w-10 h-10 rounded-full hover:bg-[#F3F4F6] flex items-center justify-center transition-colors" onclick="closeIpPanel()">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto p-6 space-y-8 custom-scrollbar">
        {{-- Hardware Specs --}}
        <section>
            <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-4 pb-2 border-b border-[#E5E7EB]">
                Información Técnica
            </h4>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div class="space-y-1">
                    <p class="text-[10px] text-[#544246] font-medium">MAC Address</p>
                    <p class="font-mono text-sm" id="panel-mac">—</p>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] text-[#544246] font-medium">Tipo Conexión</p>
                    <p class="text-sm flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">settings_ethernet</span>
                        Ethernet
                    </p>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] text-[#544246] font-medium">Usuario</p>
                    <p class="text-sm font-bold" id="panel-ip-user">—</p>
                </div>
                <div class="space-y-1">
                    <p class="text-[10px] text-[#544246] font-medium">Área</p>
                    <p class="text-sm" id="panel-ip-area">—</p>
                </div>
            </div>
        </section>

        {{-- Permisos de Navegación --}}
        <section>
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-[#E5E7EB]">
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#544246]">Permisos de Navegación</h4>
                <span class="text-[10px] bg-[#D4C19C]/20 text-[#621132] px-2 py-0.5 font-bold rounded">Perfil: Estándar</span>
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
                <div class="p-3 bg-[#fbf9f8] border border-[#E5E7EB] rounded flex flex-col items-center gap-1 text-center {{ !$p['ok'] ? 'grayscale opacity-60' : '' }}">
                    <span class="material-symbols-outlined {{ $p['ok'] ? 'text-[#166534]' : 'text-[#991B1B]' }}">{{ $p['icon'] }}</span>
                    <span class="text-[10px] font-medium leading-tight">{{ $p['label'] }}</span>
                    <span class="material-symbols-outlined text-xs {{ $p['ok'] ? 'text-[#166534]' : 'text-[#991B1B]' }}"
                          style="font-variation-settings:'FILL' 1">{{ $p['ok'] ? 'check_circle' : 'cancel' }}</span>
                </div>
                @endforeach
            </div>
        </section>

        {{-- Historial Reciente --}}
        <section>
            <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-4 pb-2 border-b border-[#E5E7EB]">Historial Reciente</h4>
            <div class="text-center text-[#544246] py-6">
                <span class="material-symbols-outlined text-3xl block mb-2">history</span>
                <p class="text-sm">Sin historial disponible</p>
            </div>
        </section>
    </div>

    <div class="p-6 border-t border-[#E5E7EB] bg-[#F3F4F6] flex gap-3">
        <button class="flex-1 py-3 bg-[#621132] text-white font-bold rounded-lg text-sm hover:opacity-90 transition-opacity">Editar Configuración</button>
        <button class="flex-1 py-3 bg-white border border-[#D4C19C] text-[#621132] font-bold rounded-lg text-sm hover:bg-[#D4C19C]/10 transition-colors">Liberar IP</button>
    </div>
</div>

{{-- Backdrop --}}
<div class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[55] hidden" id="ip-backdrop" onclick="closeIpPanel()"></div>

<script>
function switchNetTab(tab) {
    const isRangos = tab === 'rangos';
    document.getElementById('view-rangos').classList.toggle('hidden', !isRangos);
    document.getElementById('view-inventario').classList.toggle('hidden', isRangos);

    const activeClass = 'px-6 py-3 text-[11px] font-bold uppercase tracking-wider border-b-2 border-[#621132] text-[#621132] transition-all';
    const inactiveClass = 'px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-[#544246] hover:text-[#621132] transition-all';
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
</script>
</x-layouts.app>
