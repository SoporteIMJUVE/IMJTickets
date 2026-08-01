@props([
    'titulo',
    'tab'              => 'tabla',
    'conteo'           => null,
    'exportUrl'        => null,
    'importar'         => true,
    'importValidarUrl' => null,
    'importAplicarUrl' => null,
])

{{-- Barra de título + botones --}}
<div class="px-6 py-4 border-b border-border flex justify-between items-center">
    <div class="flex items-center gap-3">
        <h3 class="font-bold text-lg">{{ $titulo }}</h3>
        @if($conteo !== null)
            <span class="text-xs text-muted" id="conteo-{{ $tab }}">{{ $conteo }}</span>
        @endif
    </div>
    <div class="flex gap-2 items-center">
        {{ $acciones ?? '' }}

        {{-- Importar: visible cuando hay exportUrl Y el módulo lo permite --}}
        @if($exportUrl && $importar)
        <button onclick="abrirModalImportar('{{ $tab }}')"
                class="px-3 py-1.5 border border-border rounded text-sm font-bold flex items-center gap-2 hover:bg-wash transition-colors text-muted">
            <span class="material-symbols-outlined text-sm">upload</span> Importar
        </button>
        @endif

        {{-- Filtrar: siempre visible --}}
        <button onclick="toggleFiltros('{{ $tab }}')"
                id="btn-filtros-{{ $tab }}"
                class="px-3 py-1.5 border border-border rounded text-sm font-bold flex items-center gap-2 hover:bg-wash transition-colors">
            <span class="material-symbols-outlined text-sm">filter_list</span> Filtrar
        </button>

        {{-- Exportar: siempre visible --}}
        <button onclick="abrirModalExportar('{{ $tab }}')"
                class="px-3 py-1.5 bg-brand text-white rounded text-sm font-bold flex items-center gap-2 hover:opacity-90 transition-colors">
            <span class="material-symbols-outlined text-sm">download</span> Exportar
        </button>
    </div>
</div>

{{-- Panel de filtros colapsable --}}
@if(isset($filtros))
<div id="filtros-{{ $tab }}"
     class="hidden border-b border-border px-6 py-3 bg-[#F9FAFB] flex flex-wrap gap-3 items-end">
    {{ $filtros }}
    <button onclick="limpiarFiltros('{{ $tab }}')"
            class="text-xs font-bold text-muted hover:text-brand px-2 py-1.5 rounded hover:bg-canvas transition-colors">
        Limpiar
    </button>
    <p class="ml-auto text-xs text-muted" id="f-{{ $tab }}-count"></p>
</div>
@endif

{{-- Registro de URLs por tab (por instancia, no @once) --}}
<script>
@if($exportUrl)
window._exportUrls = window._exportUrls || {};
window._exportUrls['{{ $tab }}'] = '{{ $exportUrl }}';
@endif
@if($importValidarUrl)
window._importValidarUrls = window._importValidarUrls || {};
window._importValidarUrls['{{ $tab }}'] = '{{ $importValidarUrl }}';
window._importAplicarUrls = window._importAplicarUrls || {};
window._importAplicarUrls['{{ $tab }}'] = '{{ $importAplicarUrl }}';
@endif
</script>

{{-- Modales + funciones JS (solo una vez en el DOM) --}}
@once

{{-- ── Modal Exportar ───────────────────────────────────────────────── --}}
<div id="modal-exportar"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     style="background:rgba(0,0,0,.35)">
    <div class="bg-canvas rounded-2xl shadow-xl w-full max-w-sm mx-4 overflow-hidden">
        <div class="px-6 py-5 border-b border-border flex items-center gap-3">
            <span class="material-symbols-outlined text-brand">download</span>
            <h3 class="font-bold text-base">Exportar datos</h3>
        </div>
        <div class="px-6 py-5 text-sm text-muted">
            <p id="modal-exportar-texto">Se exportará la tabla con los <span class="font-bold text-ink">filtros activos</span> en este momento como archivo CSV.</p>
            <p id="modal-exportar-nota" class="mt-2 text-xs text-muted opacity-70 hidden"></p>
        </div>
        <div class="px-6 py-4 border-t border-border flex justify-end gap-3">
            <button onclick="cerrarModalExportar()"
                    class="px-4 py-2 border border-border rounded-lg text-sm font-bold text-muted hover:bg-wash transition-colors">
                Cancelar
            </button>
            <button id="btn-modal-exportar-aceptar" onclick="confirmarExportar()"
                    class="px-4 py-2 bg-brand text-white rounded-lg text-sm font-bold hover:opacity-90 transition-colors disabled:opacity-40 disabled:cursor-not-allowed">
                Aceptar
            </button>
        </div>
    </div>
</div>

{{-- ── Modal Importar ───────────────────────────────────────────────── --}}
<div id="modal-importar"
     class="fixed inset-0 z-50 hidden items-center justify-center"
     style="background:rgba(0,0,0,.35)">
    <div class="bg-canvas rounded-2xl shadow-xl w-full max-w-lg mx-4 overflow-hidden flex flex-col" style="max-height:90vh">
        <div class="px-6 py-5 border-b border-border flex items-center gap-3 shrink-0">
            <span class="material-symbols-outlined text-status-low">upload</span>
            <h3 class="font-bold text-base">Importar datos</h3>
        </div>

        {{-- Phase: inicio --}}
        <div id="imp-fase-inicio" class="px-6 py-5 space-y-4 overflow-y-auto">
            <div class="bg-[#FEF3C7] border border-[#D97706]/40 rounded-lg px-4 py-3 flex gap-3">
                <span class="material-symbols-outlined text-[#D97706] shrink-0 text-sm mt-0.5">warning</span>
                <p class="text-sm text-[#92400E]">El formato es un <strong>Excel (.xlsx)</strong> con restricciones. Archivos con estructura incorrecta serán rechazados.</p>
            </div>

            {{-- Paso 1 --}}
            <div class="bg-wash border border-border rounded-lg px-4 py-4">
                <p class="text-sm font-bold text-ink mb-1">Paso 1 — Descarga el formato con tus datos precargados</p>
                <p class="text-xs text-muted mb-3">Se descarga con los filtros activos. Solo incluye equipos <strong>sin resguardo</strong>. Edita las celdas blancas; no modifiques las grises ni el número de serie.</p>
                <button onclick="descargarFormato()"
                        class="flex items-center gap-2 px-3 py-1.5 border border-gold text-brand rounded text-sm font-bold hover:bg-surface-high transition-colors">
                    <span class="material-symbols-outlined text-sm">table_view</span> Descargar formato (.xlsx)
                </button>
            </div>

            {{-- Paso 2: condicional según si el tab tiene URL de importar --}}
            <div id="imp-paso2-activo" class="bg-wash border border-border rounded-lg px-4 py-4 hidden">
                <p class="text-sm font-bold text-ink mb-1">Paso 2 — Sube el archivo completado</p>
                <p class="text-xs text-muted mb-3">Se validará antes de aplicar cualquier cambio.</p>
                <input type="file" id="imp-file-input" accept=".xlsx,.xls" class="hidden" onchange="impArchivoSeleccionado(this)">
                <button onclick="document.getElementById('imp-file-input').click()"
                        class="flex items-center gap-2 px-3 py-1.5 border border-border rounded text-sm font-bold hover:bg-canvas transition-colors">
                    <span class="material-symbols-outlined text-sm">upload_file</span> Seleccionar archivo Excel
                </button>
                <p id="imp-nombre-archivo" class="mt-2 text-xs text-muted hidden"></p>
                <button id="imp-btn-validar" onclick="impValidar()"
                        class="mt-3 hidden flex items-center gap-2 px-4 py-2 bg-brand text-white rounded text-sm font-bold hover:opacity-90 transition-colors">
                    <span class="material-symbols-outlined text-sm">checklist</span> Validar archivo
                </button>
            </div>
            <div id="imp-paso2-proximamente" class="bg-wash border border-border rounded-lg px-4 py-4 opacity-50 select-none hidden">
                <p class="text-sm font-bold text-ink mb-1">Paso 2 — Sube el archivo completado</p>
                <p class="text-xs text-muted">La carga de archivos no está disponible para este módulo.</p>
            </div>
        </div>

        {{-- Phase: validando --}}
        <div id="imp-fase-validando" class="px-6 py-10 flex-col items-center justify-center gap-4 hidden">
            <div class="w-10 h-10 border-4 border-brand border-t-transparent rounded-full animate-spin"></div>
            <p class="text-sm text-muted">Validando archivo…</p>
        </div>

        {{-- Phase: preview --}}
        <div id="imp-fase-preview" class="flex flex-col overflow-hidden hidden" style="max-height:70vh">
            <div class="px-6 pt-5 pb-3 shrink-0">
                <div class="flex gap-4 text-sm">
                    <span class="flex items-center gap-1 text-green-700 font-bold"><span class="material-symbols-outlined text-base">check_circle</span> <span id="imp-cnt-ok">0</span> actualizables</span>
                    <span class="flex items-center gap-1 text-amber-700 font-bold"><span class="material-symbols-outlined text-base">info</span> <span id="imp-cnt-sc">0</span> sin cambios</span>
                    <span class="flex items-center gap-1 text-red-700 font-bold"><span class="material-symbols-outlined text-base">cancel</span> <span id="imp-cnt-err">0</span> errores</span>
                </div>
            </div>
            <div id="imp-detalle" class="px-6 pb-4 overflow-y-auto flex-1 space-y-1 text-xs"></div>
        </div>

        {{-- Phase: aplicando --}}
        <div id="imp-fase-aplicando" class="px-6 py-10 flex-col items-center justify-center gap-4 hidden">
            <div class="w-10 h-10 border-4 border-brand border-t-transparent rounded-full animate-spin"></div>
            <p class="text-sm text-muted">Aplicando cambios…</p>
        </div>

        {{-- Phase: listo --}}
        <div id="imp-fase-listo" class="px-6 py-8 flex-col items-center justify-center gap-3 hidden">
            <span class="material-symbols-outlined text-5xl text-green-600">check_circle</span>
            <p class="text-sm font-bold text-ink" id="imp-listo-msg"></p>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t border-border flex justify-between items-center shrink-0">
            <button id="imp-btn-volver" onclick="impVolver()" class="hidden px-4 py-2 border border-border rounded-lg text-sm font-bold text-muted hover:bg-wash transition-colors">
                ← Volver
            </button>
            <div class="ml-auto flex gap-3">
                <button onclick="cerrarModalImportar()"
                        class="px-4 py-2 border border-border rounded-lg text-sm font-bold text-muted hover:bg-wash transition-colors">
                    Cerrar
                </button>
                <button id="imp-btn-aplicar" onclick="impAplicar()"
                        class="hidden px-4 py-2 bg-green-700 text-white rounded-lg text-sm font-bold hover:opacity-90 transition-colors">
                    Aplicar <span id="imp-btn-aplicar-cnt"></span> cambios
                </button>
            </div>
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
    if (btn) btn.classList.toggle('bg-wash', !abierto);
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
        if (texto) texto.innerHTML = 'Se exportará la tabla con los <span class="font-bold text-ink">filtros activos</span> en este momento como archivo CSV.';
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
        if (btn) btn.classList.add('bg-wash');
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
let _impFile = null;
let _impPreviewData = null;

function abrirModalImportar(tab) {
    _importTab = tab;
    _impFile = null;
    _impPreviewData = null;

    // Show/hide paso 2 based on whether this tab has an import URL
    const tieneImport = !!(window._importValidarUrls || {})[tab];
    document.getElementById('imp-paso2-activo').classList.toggle('hidden', !tieneImport);
    document.getElementById('imp-paso2-proximamente').classList.toggle('hidden', tieneImport);

    // Reset state
    impSetFase('inicio');
    document.getElementById('imp-file-input').value = '';
    document.getElementById('imp-nombre-archivo').classList.add('hidden');
    document.getElementById('imp-btn-validar').classList.add('hidden');

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
    const tab     = _importTab;
    const baseUrl = (window._exportUrls || {})[tab];
    if (!baseUrl) return;
    const params = new URLSearchParams({ solo_encabezados: '1' });
    const panel  = document.getElementById('filtros-' + tab);
    if (panel) {
        panel.querySelectorAll('select[id], input[id]').forEach(el => {
            if (el.value && el.value.trim()) params.set(el.id, el.value.trim());
        });
    }
    window.location.href = baseUrl + '?' + params.toString();
}

function impSetFase(fase) {
    ['inicio','validando','preview','aplicando','listo'].forEach(f => {
        const el = document.getElementById('imp-fase-' + f);
        if (!el) return;
        const show = f === fase;
        el.classList.toggle('hidden', !show);
        el.classList.toggle('flex', show && f !== 'inicio' && f !== 'preview');
    });
    document.getElementById('imp-btn-volver').classList.toggle('hidden', fase === 'inicio' || fase === 'aplicando' || fase === 'listo');
    document.getElementById('imp-btn-aplicar').classList.toggle('hidden', fase !== 'preview');
}

function impVolver() {
    _impPreviewData = null;
    impSetFase('inicio');
}

function impArchivoSeleccionado(input) {
    _impFile = input.files[0] || null;
    const nombreEl = document.getElementById('imp-nombre-archivo');
    const btnVal   = document.getElementById('imp-btn-validar');
    if (_impFile) {
        nombreEl.textContent = '📄 ' + _impFile.name;
        nombreEl.classList.remove('hidden');
        btnVal.classList.remove('hidden');
    } else {
        nombreEl.classList.add('hidden');
        btnVal.classList.add('hidden');
    }
}

async function impValidar() {
    if (!_impFile) return;
    const validarUrl = (window._importValidarUrls || {})[_importTab];
    if (!validarUrl) return;

    impSetFase('validando');

    const fd = new FormData();
    fd.append('archivo', _impFile);
    fd.append('_token', document.querySelector('meta[name="csrf-token"]')?.content ?? '');

    try {
        const res  = await fetch(validarUrl, { method: 'POST', body: fd });
        const data = await res.json();

        if (!res.ok) {
            alert(data.error ?? 'Error al validar el archivo.');
            impSetFase('inicio');
            return;
        }

        _impPreviewData = data;
        impMostrarPreview(data);
        impSetFase('preview');
    } catch (err) {
        alert('Error de conexión al validar.');
        impSetFase('inicio');
    }
}

function impMostrarPreview(data) {
    document.getElementById('imp-cnt-ok').textContent  = data.actualizables ?? 0;
    document.getElementById('imp-cnt-sc').textContent  = data.sin_cambios   ?? 0;
    document.getElementById('imp-cnt-err').textContent = data.errores        ?? 0;

    const aplicarBtn = document.getElementById('imp-btn-aplicar');
    const cntLabel   = document.getElementById('imp-btn-aplicar-cnt');
    const ok         = data.actualizables ?? 0;
    aplicarBtn.disabled = ok === 0;
    if (cntLabel) cntLabel.textContent = ok > 0 ? `(${ok})` : '';

    const detalleEl = document.getElementById('imp-detalle');
    detalleEl.innerHTML = '';

    (data.detalle ?? []).forEach(item => {
        const color = item.estado === 'ok' ? 'text-green-700' : item.estado === 'sin_cambios' ? 'text-muted' : 'text-red-700';
        const icon  = item.estado === 'ok' ? '✅' : item.estado === 'sin_cambios' ? '➖' : '❌';
        const div = document.createElement('div');
        div.className = 'py-1.5 border-b border-border/50';

        let body = `<span class="font-mono font-bold">${icon} Fila ${item.fila} — ${item.serie}</span>`;
        if (item.estado === 'ok' && item.cambios?.length) {
            body += '<ul class="mt-0.5 pl-4 space-y-0.5">';
            item.cambios.forEach(c => { body += `<li class="text-muted">${c}</li>`; });
            body += '</ul>';
        } else if (item.razon) {
            body += `<p class="${color} mt-0.5">${item.razon}</p>`;
        }
        div.innerHTML = body;
        detalleEl.appendChild(div);
    });
}

async function impAplicar() {
    if (!_impFile) return;
    const aplicarUrl = (window._importAplicarUrls || {})[_importTab];
    if (!aplicarUrl) return;

    impSetFase('aplicando');

    try {
        const res  = await fetch(aplicarUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
        });
        const data = await res.json();

        if (!res.ok) {
            alert(data.error ?? 'Error al aplicar los cambios.');
            impSetFase('preview');
            return;
        }

        const msg = document.getElementById('imp-listo-msg');
        msg.textContent = `Se actualizaron ${data.actualizables ?? 0} equipo(s) correctamente.`;
        impSetFase('listo');
    } catch (err) {
        alert('Error de conexión al aplicar.');
        impSetFase('preview');
    }
}

// ── Cerrar modales al hacer clic fuera ───────────────────────────────
document.addEventListener('click', function(e) {
    const mExp = document.getElementById('modal-exportar');
    if (mExp && !mExp.classList.contains('hidden') && e.target === mExp) cerrarModalExportar();
    const mImp = document.getElementById('modal-importar');
    if (mImp && !mImp.classList.contains('hidden') && e.target === mImp) cerrarModalImportar();
});

// Los modales son fixed pero quedan atrapados en el primer tab que los renderizó.
// Si ese tab tiene display:none, los modales son invisibles aunque sean fixed.
// Moverlos al body los libera de cualquier ancestro con display:none.
['modal-exportar', 'modal-importar'].forEach(function(id) {
    const el = document.getElementById(id);
    if (el) document.body.appendChild(el);
});
</script>
@endonce
