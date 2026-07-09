<x-layouts.app title="Kardex — IMJUVE CRM">
<div class="p-8" id="kardex-page">

    {{-- Header --}}
    <div class="flex justify-between items-end mb-8">
        <div>
            <h2 class="text-[32px] font-bold leading-10 tracking-tight text-[#621132]">Kardex de Insumos y Resguardos</h2>
            <p class="text-[#544246] text-sm mt-1">Control de inventario técnico y asignación institucional de recursos.</p>
        </div>
        <div class="flex items-center gap-3">
        <a href="{{ route('kardex.resguardo.subir') }}"
           class="px-4 py-2 bg-[#621132] text-white text-sm font-bold rounded-lg hover:opacity-90 active:scale-95 transition-all flex items-center gap-2">
            <span class="material-symbols-outlined text-sm">upload_file</span>
            Registrar equipo
        </a>
        <div class="flex gap-2 bg-[#efeded] rounded-lg p-1">
            <button id="tab-btn-equipos" onclick="switchKardexTab('equipos', this)"
                    class="px-6 py-2 rounded-md text-sm font-bold transition-all text-[#544246] hover:bg-[#eae8e7]">
                Equipos
            </button>
            <button id="tab-btn-insumos" onclick="switchKardexTab('insumos', this)"
                    class="px-6 py-2 rounded-md text-sm font-bold transition-all bg-white text-[#621132] shadow-sm">
                Insumos
            </button>
            <button id="tab-btn-resguardos" onclick="switchKardexTab('resguardos', this)"
                    class="px-6 py-2 rounded-md text-sm font-bold transition-all text-[#544246] hover:bg-[#eae8e7]">
                Resguardos
            </button>
        </div>
        </div>{{-- /flex items-center gap-3 --}}
    </div>

    {{-- ---- TAB: EQUIPOS ---- --}}
    <div id="tab-equipos" class="hidden">
        {{-- Stats --}}
        <div class="grid grid-cols-4 gap-4 mb-8">
            @php $equipStats = [
                ['label'=>'Total Equipos',    'value'=>$totalEquipos,  'icon'=>'computer',       'color'=>'#166534'],
                ['label'=>'En Almacén',        'value'=>$enAlmacen,     'icon'=>'inventory',      'color'=>'#1E40AF'],
                ['label'=>'Mantenimiento',     'value'=>$mantenimiento, 'icon'=>'build',          'color'=>'#9A3412'],
                ['label'=>'Críticos',          'value'=>$criticos,      'icon'=>'report_problem', 'color'=>'#991B1B'],
            ]; @endphp
            @foreach($equipStats as $s)
            <div class="bg-white border border-[#E5E7EB] p-6 rounded-xl flex items-center gap-4">
                <div class="w-12 h-12 rounded-full flex items-center justify-center" style="background:{{ $s['color'] }}1a; color:{{ $s['color'] }}">
                    <span class="material-symbols-outlined">{{ $s['icon'] }}</span>
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246]">{{ $s['label'] }}</p>
                    <p class="text-2xl font-bold leading-tight">{{ $s['value'] }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Table --}}
        <div class="bg-white border border-[#E5E7EB] rounded-xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-[#E5E7EB] flex justify-between items-center">
                <h3 class="font-bold text-lg">Inventario de Equipos</h3>
                <div class="flex gap-2">
                    <button class="px-3 py-1.5 border border-[#E5E7EB] rounded text-sm font-bold flex items-center gap-2 hover:bg-[#F3F4F6]">
                        <span class="material-symbols-outlined text-sm">filter_list</span> Filtrar
                    </button>
                    <button class="px-3 py-1.5 bg-[#621132] text-white rounded text-sm font-bold flex items-center gap-2 hover:opacity-90">
                        <span class="material-symbols-outlined text-sm">download</span> Exportar
                    </button>
                </div>
            </div>
            <table class="w-full text-left">
                <thead class="bg-[#F3F4F6] border-b border-[#E5E7EB]">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">No. Inventario</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Tipo</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Nombre</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Responsable</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Estado</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246] text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E7EB]">
                    @forelse($equipos as $eq)
                    <tr class="hover:bg-[#D4C19C]/5 transition-colors">
                        <td class="px-6 py-3 font-mono text-sm text-[#621132]">{{ $eq->num_inventario ?? '—' }}</td>
                        <td class="px-6 py-3 text-sm">
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase
                                {{ $eq->tipo === 'Laptop' ? 'bg-[#1E40AF]/10 text-[#1E40AF]' : 'bg-[#166534]/10 text-[#166534]' }}">
                                {{ $eq->tipo }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm">{{ $eq->nombre_equipo ?? '—' }}</td>
                        <td class="px-6 py-3 text-sm">{{ $eq->nombre_usuario ?? ($eq->empleado_nombre ? trim($eq->empleado_nombre) : '—') }}</td>
                        <td class="px-6 py-3 text-sm">
                            @if($eq->id_empleado)
                            <span class="bg-[#166534]/10 text-[#166534] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Asignado</span>
                            @else
                            <span class="bg-[#1E40AF]/10 text-[#1E40AF] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Almacén</span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-right">
                            <button class="p-1.5 hover:bg-[#F3F4F6] rounded text-[#544246] hover:text-[#621132]">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-[#544246] text-sm">Sin equipos registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ---- TAB: INSUMOS (active by default) ---- --}}
    <div id="tab-insumos">
        {{-- Stats --}}
        <div class="grid grid-cols-4 gap-4 mb-8">
            <div class="bg-white border border-[#E5E7EB] p-6 rounded-xl shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-2">Total Items</p>
                <p class="text-2xl font-bold">{{ $totalInsumos }}</p>
                <div class="mt-4 flex items-center gap-1 text-[#166534] text-xs font-bold">
                    <span class="material-symbols-outlined text-sm">trending_up</span> Actualizado
                </div>
            </div>
            <div class="bg-white border border-[#E5E7EB] p-6 rounded-xl shadow-sm border-l-4 border-l-[#991B1B]">
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#991B1B] mb-2">Stock Crítico</p>
                <p class="text-2xl font-bold">{{ $stockCritico }}</p>
                <p class="mt-4 text-xs text-[#544246]">Requiere atención</p>
            </div>
            <div class="bg-white border border-[#E5E7EB] p-6 rounded-xl shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-2">Salidas recientes</p>
                <p class="text-2xl font-bold">—</p>
                <p class="mt-4 text-xs text-[#544246]">Últimas 24 horas</p>
            </div>
            <div class="bg-white border border-[#E5E7EB] p-6 rounded-xl shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-2">Valor en Almacén</p>
                <p class="text-2xl font-bold">—</p>
                <p class="mt-4 text-xs text-[#544246]">Solo insumos</p>
            </div>
        </div>

        <div class="bg-white border border-[#E5E7EB] rounded-xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-[#E5E7EB] flex justify-between items-center">
                <h3 class="font-bold text-lg">Inventario de Insumos</h3>
                <div class="flex gap-2">
                    <button class="px-3 py-1.5 border border-[#E5E7EB] rounded text-sm font-bold flex items-center gap-2 hover:bg-[#F3F4F6]">
                        <span class="material-symbols-outlined text-sm">filter_list</span> Filtrar
                    </button>
                    <button class="px-3 py-1.5 bg-[#621132] text-white rounded text-sm font-bold flex items-center gap-2 hover:opacity-90">
                        <span class="material-symbols-outlined text-sm">download</span> Exportar
                    </button>
                </div>
            </div>
            <table class="w-full text-left">
                <thead class="bg-[#F3F4F6] border-b border-[#E5E7EB]">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Part Number</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Descripción</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Categoría</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Stock</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Estado</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E7EB]">
                    @forelse($insumos as $ins)
                    @php $stockOk = $ins->stock_actual >= $ins->stock_minimo; @endphp
                    <tr class="hover:bg-[#D4C19C]/5 transition-colors cursor-pointer"
                        onclick="openInsumoPanel('{{ $ins->numero_parte ?? '—' }}', '{{ addslashes($ins->nombre_insumo) }}', {{ $ins->stock_actual }})">
                        <td class="px-6 py-3 font-mono text-sm text-[#621132]">{{ $ins->numero_parte ?? '—' }}</td>
                        <td class="px-6 py-3 text-sm font-medium">{{ $ins->nombre_insumo }}</td>
                        <td class="px-6 py-3 text-sm text-[#544246]">Consumible</td>
                        <td class="px-6 py-3 font-mono text-sm">{{ $ins->stock_actual }}</td>
                        <td class="px-6 py-3">
                            @if($stockOk)
                            <span class="bg-[#166534]/10 text-[#166534] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">OK</span>
                            @else
                            <span class="bg-[#991B1B]/10 text-[#991B1B] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Crítico</span>
                            @endif
                        </td>
                        <td class="px-6 py-3">
                            <button class="p-1.5 hover:bg-[#F3F4F6] rounded text-[#544246] hover:text-[#621132]">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-10 text-center text-[#544246] text-sm">Sin insumos registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ---- TAB: RESGUARDOS ---- --}}
    <div id="tab-resguardos" class="hidden">
        <div class="bg-white border border-[#E5E7EB] rounded-xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-[#E5E7EB] flex justify-between items-center">
                <h3 class="font-bold text-lg">Personal con Activo Fijo</h3>
                <div class="relative">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#544246] text-sm">search</span>
                    <input class="bg-[#F3F4F6] border-none rounded-lg py-2 pl-9 pr-4 text-sm w-64 outline-none focus:ring-2 focus:ring-[#621132]"
                           placeholder="Buscar empleado...">
                </div>
            </div>
            <table class="w-full text-left">
                <thead class="bg-[#F3F4F6] border-b border-[#E5E7EB]">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Empleado</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Dirección / Área</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Total Activos</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Última Actualización</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#E5E7EB]">
                    @forelse($resguardos as $res)
                    @php
                        $iniciales = strtoupper(substr($res->nombre ?? 'U', 0, 1) . substr($res->apellido_paterno ?? '', 0, 1));
                        $nombreCompleto = trim("{$res->nombre} {$res->apellido_paterno}");
                    @endphp
                    <tr class="hover:bg-[#D4C19C]/5 transition-colors cursor-pointer"
                        onclick="openResguardoPanel('{{ $res->id_empleado }}', '{{ addslashes($nombreCompleto) }}')">
                        <td class="px-6 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-[#D4C19C] flex items-center justify-center text-[#621132] font-bold text-xs">
                                    {{ $iniciales }}
                                </div>
                                <span class="text-sm font-medium">{{ $nombreCompleto }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-3 text-sm text-[#544246]">{{ $res->departamento_nombre ?? '—' }}</td>
                        <td class="px-6 py-3 font-mono text-sm">{{ $res->total_activos }}</td>
                        <td class="px-6 py-3 text-sm text-[#544246]">{{ $res->updated_at ? \Carbon\Carbon::parse($res->updated_at)->format('d/m/Y') : '—' }}</td>
                        <td class="px-6 py-3">
                            <button onclick="event.stopPropagation(); openResguardoPanel('{{ $res->id_empleado }}', '{{ addslashes($nombreCompleto) }}')"
                                    class="p-1.5 hover:bg-[#F3F4F6] rounded text-[#544246] hover:text-[#621132]">
                                <span class="material-symbols-outlined text-sm">description</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-[#544246] text-sm">Sin resguardos registrados</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Side Panel: Insumo Detail --}}
<div class="fixed top-0 right-0 h-screen w-[400px] bg-white shadow-2xl border-l border-[#D4C19C] z-[60] detail-panel closed flex flex-col" id="insumo-panel">
    <div class="p-6 border-b border-[#E5E7EB] bg-[#fbf9f8] flex justify-between items-center">
        <div>
            <p class="font-mono text-xs text-[#621132]" id="panel-part-number">—</p>
            <h4 class="text-xl font-bold" id="panel-insumo-name">Insumo</h4>
        </div>
        <button class="text-[#544246] hover:text-[#621132]" onclick="closeInsumoPanel()">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <div class="flex-1 overflow-y-auto p-6 custom-scrollbar">
        <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-4">EXISTENCIAS ACTUALES</p>
        <div class="flex items-center justify-between p-4 bg-[#991B1B]/5 border border-[#991B1B]/20 rounded-lg mb-6">
            <span class="font-bold">— Unidades</span>
            <span class="px-2 py-0.5 rounded bg-[#991B1B] text-white text-[10px] font-bold uppercase">Crítico</span>
        </div>
        <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-4">HISTORIAL DE MOVIMIENTOS</p>
        <div class="text-center text-[#544246] py-8">
            <span class="material-symbols-outlined text-3xl block mb-2">history</span>
            <p class="text-sm">Selecciona un insumo para ver su historial</p>
        </div>
    </div>
    <div class="p-6 border-t border-[#E5E7EB]">
        <button class="w-full py-3 bg-[#621132] text-white font-bold rounded hover:opacity-90 flex items-center justify-center gap-2 text-sm">
            <span class="material-symbols-outlined">add_box</span>
            Registrar Entrada / Salida
        </button>
    </div>
</div>

{{-- Side Panel: Resguardo Preview --}}
<div class="fixed top-0 right-0 h-screen w-[400px] bg-white shadow-2xl border-l border-[#D4C19C] z-[60] detail-panel closed flex flex-col" id="resguardo-panel">
    <div class="p-6 border-b border-[#E5E7EB] bg-[#fbf9f8] flex justify-between items-center">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246]">Vista Previa</p>
            <h4 class="text-xl font-bold">Resguardo Individual</h4>
        </div>
        <button class="text-[#544246] hover:text-[#621132]" onclick="closeResguardoPanel()">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>
    <div class="flex-1 overflow-y-auto p-6 bg-[#F3F4F6] custom-scrollbar">
        {{-- A4-like document preview --}}
        <div class="bg-white shadow p-6 border border-[#E5E7EB] text-[10px]">
            <div class="flex justify-between items-start mb-6 pb-4 border-b border-gray-100">
                <div class="w-14 h-14 bg-[#621132]/10 flex items-center justify-center text-[#621132] font-bold text-xs">LOGO</div>
                <div class="text-right text-[10px]">
                    <p class="font-bold">Instituto Mexicano de la Juventud</p>
                    <p>Dirección de Administración y Finanzas</p>
                    <p>Subdirección de Tecnologías de la Información</p>
                </div>
            </div>
            <h5 class="text-center font-bold text-xs mb-4 underline">RESGUARDO DE EQUIPO Y MOBILIARIO</h5>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div><p><span class="font-bold">Folio:</span> —</p></div>
                <div><p><span class="font-bold">Fecha:</span> —</p></div>
                <div><p><span class="font-bold">Usuario:</span> —</p></div>
                <div><p><span class="font-bold">Área:</span> —</p></div>
            </div>
            <table class="w-full text-left mb-8">
                <thead><tr class="bg-gray-100 font-bold border-b">
                    <th class="p-1">No. Inv.</th>
                    <th class="p-1">Descripción</th>
                    <th class="p-1">No. Serie</th>
                </tr></thead>
                <tbody class="divide-y divide-gray-100">
                    <tr><td colspan="3" class="p-2 text-center text-gray-400 italic">Sin activos asignados</td></tr>
                </tbody>
            </table>
            <div class="grid grid-cols-2 gap-8 text-center pt-4 mt-4">
                <div><div class="border-t border-black mb-1"></div><p class="font-bold">Entrega</p><p>Soporte Técnico IT</p></div>
                <div><div class="border-t border-black mb-1"></div><p class="font-bold">Recibe</p><p>—</p></div>
            </div>
        </div>
    </div>
    <div class="p-6 border-t border-[#E5E7EB] flex gap-3">
        <button class="flex-1 py-3 bg-[#621132] text-white font-bold rounded hover:opacity-90 flex items-center justify-center gap-2 text-sm">
            <span class="material-symbols-outlined text-sm">download</span> PDF
        </button>
        <button class="p-3 border border-[#E5E7EB] rounded hover:bg-[#F3F4F6] transition-all">
            <span class="material-symbols-outlined">print</span>
        </button>
    </div>
</div>

<div class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[55] hidden" id="kardex-backdrop"
     onclick="closeInsumoPanel(); closeResguardoPanel(); document.getElementById('kardex-backdrop').classList.add('hidden');"></div>

<script>
const ACTIVE_TAB = 'px-6 py-2 rounded-md text-sm font-bold transition-all bg-white text-[#621132] shadow-sm';
const INACTIVE_TAB = 'px-6 py-2 rounded-md text-sm font-bold transition-all text-[#544246] hover:bg-[#eae8e7]';
let currentKardexTab = 'insumos';

function switchKardexTab(tab, btn) {
    ['equipos', 'insumos', 'resguardos'].forEach(t => {
        document.getElementById('tab-' + t).classList.toggle('hidden', t !== tab);
        document.getElementById('tab-btn-' + t).className = t === tab ? ACTIVE_TAB : INACTIVE_TAB;
    });
    currentKardexTab = tab;
}

function openInsumoPanel(partNum, nombre, stock) {
    closeResguardoPanel();
    document.getElementById('panel-part-number').innerText = partNum || '—';
    if (document.getElementById('panel-insumo-name')) document.getElementById('panel-insumo-name').innerText = nombre || 'Insumo';
    if (document.getElementById('panel-stock-value')) document.getElementById('panel-stock-value').innerText = (stock ?? '—') + ' uds.';
    document.getElementById('insumo-panel').classList.remove('closed');
    document.getElementById('kardex-backdrop').classList.remove('hidden');
}
function closeInsumoPanel() {
    document.getElementById('insumo-panel').classList.add('closed');
}
function openResguardoPanel(empId, nombre) {
    closeInsumoPanel();
    if (document.getElementById('panel-resguardo-name')) document.getElementById('panel-resguardo-name').innerText = nombre || '';
    document.getElementById('resguardo-panel').classList.remove('closed');
    document.getElementById('kardex-backdrop').classList.remove('hidden');
}
function closeResguardoPanel() {
    document.getElementById('resguardo-panel').classList.add('closed');
}
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeInsumoPanel();
        closeResguardoPanel();
        document.getElementById('kardex-backdrop').classList.add('hidden');
    }
});
</script>
</x-layouts.app>
