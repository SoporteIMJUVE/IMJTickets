<x-layouts.app title="Registrar equipo — Resguardo PDF">
<div class="p-8 max-w-2xl mx-auto">

    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('kardex.index') }}"
           class="p-2 rounded-lg hover:bg-surface-high transition-colors text-on-surface-variant hover:text-primary-container">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="text-[28px] font-bold leading-tight tracking-tight text-primary-container">Registrar equipo</h2>
            <p class="text-on-surface-variant text-sm mt-0.5">Sube el PDF del resguardo para extraer los datos automáticamente.</p>
        </div>
    </div>

    @if($errors->any())
    <div class="mb-6 flex items-start gap-3 bg-error-container border border-red-200 text-on-surface rounded-xl px-4 py-3 text-sm font-semibold">
        <span class="material-symbols-outlined text-error mt-0.5">error</span>
        <div>
            @foreach($errors->all() as $e)
                <p>{{ $e }}</p>
            @endforeach
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('kardex.resguardo.extraer') }}" enctype="multipart/form-data">
        @csrf
        <div class="bg-canvas border border-border rounded-2xl shadow-sm overflow-hidden">

            {{-- Zona de carga --}}
            <div class="px-6 pt-6 pb-5 border-b border-surface-muted">
                <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-3">
                    Archivo PDF del resguardo
                </label>

                <label for="pdf"
                    class="flex flex-col items-center justify-center w-full h-40 border-2 border-dashed border-outline-variant rounded-xl cursor-pointer hover:border-primary-container hover:bg-surface-low transition-colors"
                    id="dropzone">
                    <span class="material-symbols-outlined text-4xl text-on-surface-variant mb-2" id="dz-icon">upload_file</span>
                    <p class="text-sm font-semibold text-on-surface-variant" id="dz-label">Haz clic o arrastra el PDF aquí</p>
                    <p class="text-xs text-on-surface-variant mt-1">Máximo 10 MB</p>
                    <input id="pdf" name="pdf" type="file" accept=".pdf" class="hidden">
                </label>
            </div>

            {{-- Aviso de calidad --}}
            <div class="px-6 py-4 bg-surface-low border-b border-surface-muted">
                <div class="flex items-start gap-3">
                    <span class="material-symbols-outlined text-status-free mt-0.5" style="font-size:18px">info</span>
                    <div class="text-xs text-on-surface-variant space-y-1">
                        <p><span class="font-bold text-on-surface">✅ PDF tipado</span> — el sistema extrae los datos automáticamente.</p>
                        <p><span class="font-bold text-on-surface">🖼️ PDF escaneado (imagen)</span> — se acepta, pero tendrás que llenar los datos a mano. El archivo se guarda como respaldo.</p>
                    </div>
                </div>
            </div>

            <div class="px-6 py-5 flex justify-end">
                <button type="submit" id="btn-submit"
                    class="px-6 py-2.5 bg-primary-container text-on-primary text-sm font-bold rounded-lg
                           hover:opacity-90 active:scale-95 transition-all flex items-center gap-2 disabled:opacity-60">
                    <span class="material-symbols-outlined text-sm">document_scanner</span>
                    Extraer datos del PDF
                </button>
            </div>
        </div>
    </form>
</div>

<script>
const inputPdf  = document.getElementById('pdf');
const dzIcon    = document.getElementById('dz-icon');
const dzLabel   = document.getElementById('dz-label');
const btnSubmit = document.getElementById('btn-submit');
const form      = document.querySelector('form');

inputPdf.addEventListener('change', function () {
    const name = this.files[0]?.name ?? '';
    if (name) {
        dzIcon.textContent  = 'picture_as_pdf';
        dzLabel.textContent = name;
        dzLabel.classList.add('text-primary-container', 'font-bold');
    }
});

form.addEventListener('submit', function () {
    if (!inputPdf.files.length) return;
    btnSubmit.disabled = true;
    btnSubmit.innerHTML = '<span class="material-symbols-outlined text-sm animate-spin">progress_activity</span> Procesando PDF…';
    dzIcon.textContent  = 'hourglass_top';
    dzLabel.textContent = 'Leyendo texto del archivo…';
});
</script>
</x-layouts.app>
