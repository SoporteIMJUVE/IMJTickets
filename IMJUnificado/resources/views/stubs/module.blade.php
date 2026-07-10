<x-layouts.app title="Módulo — IMJUVE CRM">
<div class="p-8" id="modulo-page">

    {{-- ══════════════════════════════════════════════════════════════
         Header + toggle de vista
         - h2: nombre del módulo (siempre en guinda #621132)
         - p: descripción breve en un renglón
         - Toggle de vista: solo si el módulo tiene más de una vista (tabs).
           Si tiene una sola vista, borra el bloque <div class="flex items-center gap-3">.
         - Botón de ajustes: descomenta cuando implementes configuración por módulo.
    ══════════════════════════════════════════════════════════════ --}}
    <div class="flex justify-between items-end mb-6">
        <div>
            <h2 class="text-[32px] font-bold leading-10 tracking-tight text-[#621132]">Nombre del Módulo</h2>
            <p class="text-[#544246] text-sm mt-1">Descripción breve del propósito de este módulo.</p>
        </div>
        <div class="flex items-center gap-3">

            {{-- Toggle de vista — OPCIONAL: borra si solo hay una vista --}}
            <div class="flex bg-[#F3F4F6] rounded-lg p-1">
                <button onclick="setView('vista1')" id="btn-vista1"
                        class="px-4 py-2 rounded-md text-sm font-bold transition-all bg-white text-[#621132] shadow-sm">
                    <span class="material-symbols-outlined text-sm align-middle">format_list_bulleted</span>
                    Vista 1
                </button>
                <button onclick="setView('vista2')" id="btn-vista2"
                        class="px-4 py-2 rounded-md text-sm font-bold transition-all text-[#544246] hover:bg-[#eae8e7]">
                    <span class="material-symbols-outlined text-sm align-middle">view_kanban</span>
                    Vista 2
                </button>
            </div>

            {{-- Ajustes: reemplaza el alert con la lógica real cuando implementes config por módulo --}}
            <button onclick="alert('Ajustes del módulo aún no disponibles.')"
                    class="p-2 border border-[#E5E7EB] rounded-lg hover:bg-[#F3F4F6] transition-colors text-[#544246]"
                    title="Ajustes">
                <span class="material-symbols-outlined text-sm">settings</span>
            </button>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         Stats — OPCIONAL: borra la sección completa si el módulo no
         necesita KPIs en la parte superior. Usa 3 o 4 columnas según
         cuántas métricas relevantes tenga el módulo.
    ══════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-xl border border-[#E5E7EB] shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-2">Métrica 1</p>
            <h3 class="text-3xl font-bold text-[#621132]">0</h3>
            <div class="mt-4 flex items-center gap-2 text-[#166534] text-xs">
                <span class="material-symbols-outlined text-sm">check_circle</span>
                Descripción corta
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl border border-[#E5E7EB] shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-2">Métrica 2</p>
            <h3 class="text-3xl font-bold text-[#621132]">0</h3>
            <div class="mt-4 h-2 bg-[#F3F4F6] rounded-full overflow-hidden">
                <div class="h-full bg-[#D4C19C] rounded-full" style="width:0%"></div>
            </div>
        </div>
        <div class="bg-white p-6 rounded-xl border border-[#E5E7EB] shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-2">Métrica 3</p>
            <h3 class="text-3xl font-bold text-[#991B1B]">0</h3>
            <div class="mt-4 flex items-center gap-2 text-[#991B1B] text-xs">
                <span class="material-symbols-outlined text-sm">warning</span>
                Descripción corta
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         VIEW: VISTA 1
         - Cada vista vive en su propio <div id="view-nombre">.
         - La primera vista NO lleva la clase "hidden"; las demás sí.
         - Si el módulo tiene una sola vista, no uses view-wrapping:
           pon el bloque de filtros y la tabla directamente aquí.
    ══════════════════════════════════════════════════════════════ --}}
    <div id="view-vista1">

        {{-- Botones de acciones + Barra de filtros --}}
        <div class="bg-white border border-[#E5E7EB] rounded-xl overflow-hidden shadow-sm mb-6">
            <x-tabla-encabezado
                titulo="Título de la Tabla"
                tab="modulo"
                exportUrl="{{ route('modulo.exportar') }}"
                {{-- :importar="false"  ← descomenta si este módulo no admite importar --}}
            >
                {{-- Slot acciones: botones extra a la derecha del título (Nuevo, etc.) --}}
                {{-- OPCIONAL: borra el slot completo si no hay acciones adicionales --}}
                <x-slot:acciones>
                    <button onclick="abrirModalNuevo()"
                            class="px-4 py-2 bg-[#621132] text-white rounded flex items-center gap-2 hover:opacity-90 transition-opacity text-sm font-bold">
                        <span class="material-symbols-outlined text-sm">add</span>
                        Nuevo registro
                    </button>
                </x-slot:acciones>

                {{-- Slot filtros: controles de búsqueda (select, input, date…) --}}
                {{-- OPCIONAL: borra el slot si la tabla no tiene filtros --}}
                <x-slot:filtros>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-[#544246] mb-1">Buscar</label>
                        <div class="relative">
                            <span class="material-symbols-outlined absolute left-2 top-1/2 -translate-y-1/2 text-[#544246] text-sm">search</span>
                            <input id="f-modulo-texto" type="text" oninput="filtrarTabla()"
                                   placeholder="Texto libre…"
                                   class="pl-7 pr-3 py-1.5 text-sm border border-[#E5E7EB] rounded bg-white outline-none focus:ring-2 focus:ring-[#621132] w-52">
                        </div>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold uppercase tracking-wider text-[#544246] mb-1">Estado</label>
                        <select id="f-modulo-estado" onchange="filtrarTabla()"
                                class="text-sm border border-[#E5E7EB] rounded px-3 py-1.5 bg-white outline-none focus:ring-2 focus:ring-[#621132]">
                            <option value="">Todos</option>
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                </x-slot:filtros>
            </x-tabla-encabezado>
        </div>

        {{-- Tabla principal --}}
        <div class="bg-white rounded-xl border border-[#E5E7EB] shadow-sm overflow-hidden">
            <table class="w-full text-left">
                <thead class="bg-[#F3F4F6] border-b border-[#E5E7EB]">
                    <tr>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Columna 1</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Columna 2</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Estado</th>
                        <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246] text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="modulo-tbody" class="divide-y divide-[#E5E7EB]">
                    @forelse($registros as $r)
                    <tr class="hover:bg-[#D4C19C]/5 transition-colors cursor-pointer modulo-row"
                        data-id="{{ $r->id }}"
                        data-estado="{{ $r->estado }}"
                        data-texto="{{ strtolower($r->nombre ?? '') }}"
                        onclick="openPanel(this)">
                        <td class="px-6 py-4 text-sm font-medium">{{ $r->nombre }}</td>
                        <td class="px-6 py-4 text-sm text-[#544246]">—</td>
                        <td class="px-6 py-4">
                            <span class="bg-[#166534]/10 text-[#166534] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">
                                Activo
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button class="p-1.5 hover:bg-[#F3F4F6] rounded text-[#544246] hover:text-[#621132]">
                                <span class="material-symbols-outlined text-sm">visibility</span>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-[#544246] text-sm">Sin registros</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         VIEW: VISTA 2 — OPCIONAL: borra si el módulo tiene una sola vista
    ══════════════════════════════════════════════════════════════ --}}
    <div id="view-vista2" class="hidden">
        {{-- Contenido de la segunda vista --}}
    </div>

</div>

{{-- ══════════════════════════════════════════════════════════════
     PANEL LATERAL
     - Siempre fuera del <div class="p-8">.
     - Ancho fijo 400px (420px en módulos con más contenido).
     - Estructura: header fijo → tabs (opcional) → body scrollable → footer fijo.
     - El backdrop cierra el panel al hacer clic fuera.
══════════════════════════════════════════════════════════════ --}}
<aside class="fixed top-0 right-0 h-screen w-[400px] bg-white shadow-2xl border-l border-[#D4C19C] z-[60] detail-panel closed flex flex-col"
       id="modulo-panel">

    {{-- Header fijo --}}
    <div class="p-6 border-b border-[#E5E7EB] bg-[#fbf9f8] flex justify-between items-center shrink-0">
        <div>
            <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246]">Detalles</p>
            <h2 class="text-xl font-bold text-[#621132]" id="panel-titulo">—</h2>
        </div>
        <button class="w-10 h-10 rounded-full hover:bg-[#F3F4F6] flex items-center justify-center transition-colors"
                onclick="closePanel()">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    {{-- Tabs — OPCIONAL: borra si el panel no necesita pestañas --}}
    <div class="flex border-b border-[#E5E7EB] px-6 bg-white shrink-0">
        <button class="px-4 py-3 text-[#621132] font-bold border-b-2 border-[#621132] text-sm"
                onclick="switchPanelTab('info', this)">Información</button>
        <button class="px-4 py-3 text-[#544246] font-medium text-sm hover:text-[#621132]"
                onclick="switchPanelTab('historial', this)">Historial</button>
    </div>

    {{-- Body scrollable --}}
    <div class="flex-1 overflow-y-auto custom-scrollbar">

        {{-- Tab: Información --}}
        <div class="p-6 space-y-6" id="tab-info">
            <section>
                <h4 class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-4 pb-2 border-b border-[#E5E7EB]">
                    Datos generales
                </h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div class="space-y-1">
                        <p class="text-[10px] text-[#544246] font-medium">Campo 1</p>
                        <p class="font-semibold" id="panel-campo1">—</p>
                    </div>
                    <div class="space-y-1">
                        <p class="text-[10px] text-[#544246] font-medium">Campo 2</p>
                        <p class="font-semibold" id="panel-campo2">—</p>
                    </div>
                </div>
            </section>
        </div>

        {{-- Tab: Historial — OPCIONAL --}}
        <div class="p-6 hidden" id="tab-historial">
            <div class="text-center text-[#544246] py-12">
                <span class="material-symbols-outlined text-4xl mb-2 block">history</span>
                <p class="text-sm">Sin historial disponible</p>
            </div>
        </div>

    </div>

    {{-- Footer fijo con acciones --}}
    <div class="p-6 border-t border-[#E5E7EB] bg-[#F3F4F6] flex gap-3 shrink-0">
        <button class="flex-1 py-3 bg-white border border-[#D4C19C] text-[#621132] font-bold rounded-lg text-sm hover:bg-[#D4C19C]/10 transition-colors">
            Editar
        </button>
        <button class="flex-1 py-3 bg-[#621132] text-white font-bold rounded-lg text-sm hover:opacity-90 transition-opacity">
            Acción principal
        </button>
    </div>
</aside>

{{-- Backdrop --}}
<div class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[55] hidden"
     id="modulo-backdrop" onclick="closePanel()"></div>

<script>
// ── Toggle de vista — OPCIONAL: borra si una sola vista ──────────────────────
function setView(mode) {
    ['vista1', 'vista2'].forEach(v => {
        document.getElementById('view-' + v).classList.toggle('hidden', v !== mode);
    });
    const active   = 'px-4 py-2 rounded-md text-sm font-bold transition-all bg-white text-[#621132] shadow-sm';
    const inactive = 'px-4 py-2 rounded-md text-sm font-bold transition-all text-[#544246] hover:bg-[#eae8e7]';
    document.getElementById('btn-vista1').className = mode === 'vista1' ? active : inactive;
    document.getElementById('btn-vista2').className = mode === 'vista2' ? active : inactive;
}

// ── Filtros ───────────────────────────────────────────────────────────────────
function filtrarTabla() {
    const texto  = document.getElementById('f-modulo-texto').value.toLowerCase();
    const estado = document.getElementById('f-modulo-estado').value.toLowerCase();

    document.querySelectorAll('#modulo-tbody tr.modulo-row').forEach(row => {
        const okTexto  = !texto  || (row.dataset.texto || '').includes(texto);
        const okEstado = !estado || (row.dataset.estado || '').includes(estado);
        row.style.display = (okTexto && okEstado) ? '' : 'none';
    });
}

// ── Panel lateral ─────────────────────────────────────────────────────────────
function openPanel(row) {
    document.getElementById('panel-titulo').textContent = row.dataset.id || '—';
    // Llena aquí los demás campos del panel con row.dataset.*
    document.getElementById('modulo-panel').classList.remove('closed');
    document.getElementById('modulo-backdrop').classList.remove('hidden');
}

function closePanel() {
    document.getElementById('modulo-panel').classList.add('closed');
    document.getElementById('modulo-backdrop').classList.add('hidden');
}

// ── Tabs del panel — OPCIONAL: borra si el panel no tiene tabs ───────────────
function switchPanelTab(tab, btn) {
    ['info', 'historial'].forEach(t => document.getElementById('tab-' + t).classList.add('hidden'));
    document.querySelectorAll('#modulo-panel .flex.border-b button').forEach(b => {
        b.className = 'px-4 py-3 text-[#544246] font-medium text-sm hover:text-[#621132]';
    });
    document.getElementById('tab-' + tab).classList.remove('hidden');
    if (btn) btn.className = 'px-4 py-3 text-[#621132] font-bold border-b-2 border-[#621132] text-sm';
}

document.addEventListener('keydown', e => { if (e.key === 'Escape') closePanel(); });
</script>
</x-layouts.app>
