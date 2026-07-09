@props([
    'titulo',
    'tab'       => 'tabla',
    'conteo'    => null,
    'exportUrl' => null,
])

{{-- Barra de título + botones --}}
<div class="px-6 py-4 border-b border-[#E5E7EB] flex justify-between items-center">
    <div class="flex items-center gap-3">
        <h3 class="font-bold text-lg">{{ $titulo }}</h3>
        @if($conteo !== null)
            <span class="text-xs text-[#544246]" id="conteo-{{ $tab }}">{{ $conteo }}</span>
        @endif
    </div>
    <div class="flex gap-2 items-center">
        {{ $acciones ?? '' }}

        {{-- Importar: visible solo cuando hay exportUrl (oculto en resguardos y tabs sin URL) --}}
        @if($exportUrl)
        <button onclick="abrirModalImportar('{{ $tab }}')"
                class="px-3 py-1.5 border border-[#E5E7EB] rounded text-sm font-bold flex items-center gap-2 hover:bg-[#F3F4F6] transition-colors text-[#544246]">
            <span class="material-symbols-outlined text-sm">upload</span> Importar
        </button>
        @endif

        {{-- Filtrar: siempre visible --}}
        <button onclick="toggleFiltros('{{ $tab }}')"
                id="btn-filtros-{{ $tab }}"
                class="px-3 py-1.5 border border-[#E5E7EB] rounded text-sm font-bold flex items-center gap-2 hover:bg-[#F3F4F6] transition-colors">
            <span class="material-symbols-outlined text-sm">filter_list</span> Filtrar
        </button>

        {{-- Exportar: siempre visible --}}
        <button onclick="abrirModalExportar('{{ $tab }}')"
                class="px-3 py-1.5 bg-[#621132] text-white rounded text-sm font-bold flex items-center gap-2 hover:opacity-90 transition-colors">
            <span class="material-symbols-outlined text-sm">download</span> Exportar
        </button>
    </div>
</div>

{{-- Panel de filtros colapsable --}}
@if(isset($filtros))
<div id="filtros-{{ $tab }}"
     class="hidden border-b border-[#E5E7EB] px-6 py-3 bg-[#F9FAFB] flex flex-wrap gap-3 items-end">
    {{ $filtros }}
    <button onclick="limpiarFiltros('{{ $tab }}')"
            class="text-xs font-bold text-[#544246] hover:text-[#621132] px-2 py-1.5 rounded hover:bg-white transition-colors">
        Limpiar
    </button>
    <p class="ml-auto text-xs text-[#544246]" id="f-{{ $tab }}-count"></p>
</div>
@endif

{{-- Registro de URL de exportación por tab (por instancia, no @once) --}}
@if($exportUrl)
<script>
window._exportUrls = window._exportUrls || {};
window._exportUrls['{{ $tab }}'] = '{{ $exportUrl }}';
</script>
@endif

{{-- Modales + funciones JS (solo una vez en el DOM) --}}
@once

{{-- ── Modal Exportar ───────────────────────────────────────────────── --}}
<div id="modal-exportar"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     style="background:rgba(0,0,0,.35)">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm mx-4 overflow-hidden">
        <div class="px-6 py-5 border-b border-[#E5E7EB] flex items-center gap-3">
            <span class="material-symbols-outlined text-[#621132]">download</span>
            <h3 class="font-bold text-base">Exportar datos</h3>
        </div>
        <div class="px-6 py-5 text-sm text-[#544246]">
            <p id="modal-exportar-texto">Se exportará la tabla con los <span class="font-bold text-[#1b1c1c]">filtros activos</span> en este momento como archivo CSV.</p>
            <p id="modal-exportar-nota" class="mt-2 text-xs text-[#544246] opacity-70 hidden"></p>
        </div>
        <div class="px-6 py-4 border-t border-[#E5E7EB] flex justify-end gap-3">
            <button onclick="cerrarModalExportar()"
                    class="px-4 py-2 border border-[#E5E7EB] rounded-lg text-sm font-bold text-[#544246] hover:bg-[#F3F4F6] transition-colors">
                Cancelar
            </button>
            <button id="btn-modal-exportar-aceptar" onclick="confirmarExportar()"
                    class="px-4 py-2 bg-[#621132] text-white rounded-lg text-sm font-bold hover:opacity-90 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                Aceptar
            </button>
        </div>
    </div>
</div>

{{-- ── Modal Importar ───────────────────────────────────────────────── --}}
<div id="modal-importar"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     style="background:rgba(0,0,0,.35)">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md mx-4 overflow-hidden">
        <div class="px-6 py-5 border-b border-[#E5E7EB] flex items-center gap-3">
            <span class="material-symbols-outlined text-[#9A3412]">upload</span>
            <h3 class="font-bold text-base">Importar datos</h3>
        </div>
        <div class="px-6 py-5 space-y-4">
            {{-- Advertencia --}}
            <div class="bg-[#FEF3C7] border border-[#D97706]/40 rounded-lg px-4 py-3 flex gap-3">
                <span class="material-symbols-outlined text-[#D97706] shrink-0 text-sm mt-0.5">warning</span>
                <p class="text-sm text-[#92400E]">La importación requiere un <strong>formato especial de CSV o Excel</strong>. Archivos con estructura incorrecta serán rechazados.</p>
            </div>
            {{-- Paso 1: descargar formato --}}
            <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-lg px-4 py-4">
                <p class="text-sm font-bold text-[#1b1c1c] mb-1">Paso 1 — Descargue el formato vacío</p>
                <p class="text-xs text-[#544246] mb-3">El formato contiene las columnas exactas requeridas. Llénelo con sus datos <strong>sin modificar los encabezados</strong>.</p>
                <button onclick="descargarFormato()"
                        class="flex items-center gap-2 px-3 py-1.5 border border-[#D4C19C] text-[#621132] rounded text-sm font-bold hover:bg-[#eae8e7] transition-colors">
                    <span class="material-symbols-outlined text-sm">table_view</span> Descargar formato vacío
                </button>
            </div>
            {{-- Paso 2: subir archivo (próximamente) --}}
            <div class="bg-[#F9FAFB] border border-[#E5E7EB] rounded-lg px-4 py-4 opacity-50 select-none">
                <p class="text-sm font-bold text-[#1b1c1c] mb-1">Paso 2 — Suba el archivo completado</p>
                <p class="text-xs text-[#544246]">La carga de archivos estará disponible próximamente.</p>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-[#E5E7EB] flex justify-end">
            <button onclick="cerrarModalImportar()"
                    class="px-4 py-2 border border-[#E5E7EB] rounded-lg text-sm font-bold text-[#544246] hover:bg-[#F3F4F6] transition-colors">
                Cerrar
            </button>
        </div>
    </div>
</div>

<script>
window._exportUrls = window._exportUrls || {};
let _exportTab = null;
let _importTab = null;

// ── Filtros ──────────────────────────────────────────────────────────
function toggleFiltros(tab) {
    const panel = document.getElementById('filtros-' + tab);
    const btn   = document.getElementById('btn-filtros-' + tab);
    if (!panel) return;
    const abierto = !panel.classList.contains('hidden');
    panel.classList.toggle('hidden');
    if (btn) btn.classList.toggle('bg-[#F3F4F6]', !abierto);
}

function limpiarFiltros(tab) {
    const panel = document.getElementById('filtros-' + tab);
    if (!panel) return;
    panel.querySelectorAll('select').forEach(s => { s.value = ''; s.dispatchEvent(new Event('change')); });
    panel.querySelectorAll('input[type=text], input[type=date]').forEach(i => { i.value = ''; i.dispatchEvent(new Event('input')); });
}

// ── Exportar ─────────────────────────────────────────────────────────
function abrirModalExportar(tab) {
    _exportTab = tab;
    const hasUrl  = !!(window._exportUrls || {})[tab];
    const texto   = document.getElementById('modal-exportar-texto');
    const nota    = document.getElementById('modal-exportar-nota');
    const aceptar = document.getElementById('btn-modal-exportar-aceptar');

    if (hasUrl) {
        if (texto) texto.innerHTML = 'Se exportará la tabla con los <span class="font-bold text-[#1b1c1c]">filtros activos</span> en este momento como archivo CSV.';
        if (nota)  { nota.textContent = ''; nota.classList.add('hidden'); }
        if (aceptar) aceptar.disabled = false;
    } else {
        if (texto) texto.textContent = 'La exportación no está disponible para este módulo.';
        if (nota)  { nota.textContent = 'Estará disponible próximamente.'; nota.classList.remove('hidden'); }
        if (aceptar) aceptar.disabled = true;
    }

    // Abrir panel de filtros si está cerrado para que el usuario vea qué se exportará
    const panel = document.getElementById('filtros-' + tab);
    const btn   = document.getElementById('btn-filtros-' + tab);
    if (panel && panel.classList.contains('hidden')) {
        panel.classList.remove('hidden');
        if (btn) btn.classList.add('bg-[#F3F4F6]');
    }

    const modal = document.getElementById('modal-exportar');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function cerrarModalExportar() {
    const modal = document.getElementById('modal-exportar');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function confirmarExportar() {
    const tab     = _exportTab;
    const baseUrl = (window._exportUrls || {})[tab];
    if (!baseUrl) { cerrarModalExportar(); return; }

    const params = new URLSearchParams();
    const panel  = document.getElementById('filtros-' + tab);
    if (panel) {
        panel.querySelectorAll('select[id], input[id]').forEach(el => {
            if (el.value && el.value.trim()) params.set(el.id, el.value.trim());
        });
    }

    const qs = params.toString();
    window.location.href = baseUrl + (qs ? '?' + qs : '');
    cerrarModalExportar();
}

// ── Importar ─────────────────────────────────────────────────────────
function abrirModalImportar(tab) {
    _importTab = tab;
    const modal = document.getElementById('modal-importar');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
}

function cerrarModalImportar() {
    const modal = document.getElementById('modal-importar');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function descargarFormato() {
    const baseUrl = (window._exportUrls || {})[_importTab];
    if (!baseUrl) return;
    window.location.href = baseUrl + '?solo_encabezados=1';
}

// ── Cerrar modales al hacer clic fuera ───────────────────────────────
document.addEventListener('click', function(e) {
    const mExp = document.getElementById('modal-exportar');
    if (mExp && !mExp.classList.contains('hidden') && e.target === mExp) cerrarModalExportar();
    const mImp = document.getElementById('modal-importar');
    if (mImp && !mImp.classList.contains('hidden') && e.target === mImp) cerrarModalImportar();
});
</script>
@endonce
