<x-layouts.app title="Confirmar datos — Resguardo">
<div class="p-8 max-w-4xl mx-auto">

    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('kardex.resguardo.subir') }}"
           class="p-2 rounded-lg hover:bg-surface-high transition-colors text-on-surface-variant">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="text-[28px] font-bold leading-tight tracking-tight text-primary-container">Confirmar datos extraídos</h2>
            <p class="text-on-surface-variant text-sm mt-0.5">Revisa y corrige los campos antes de guardar. Los campos con fondo amarillo fueron detectados automáticamente.</p>
        </div>
    </div>

    @if(!$esNativo)
    <div class="mb-4 flex items-start gap-3 bg-yellow-100 border border-yellow-300 text-on-surface rounded-xl px-4 py-3 text-sm">
        <span class="material-symbols-outlined text-yellow-600 mt-0.5">image_search</span>
        <div>
            <p class="font-bold text-yellow-800">PDF escaneado (imagen) — ingreso manual</p>
            <p class="text-yellow-700 mt-0.5">No se detectó texto en el archivo. Llena los campos a mano. El PDF se guardará como respaldo.</p>
        </div>
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 flex items-start gap-3 bg-error-container border border-red-200 text-on-surface rounded-xl px-4 py-3 text-sm font-semibold">
        <span class="material-symbols-outlined text-error mt-0.5">error</span>
        <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    </div>
    @endif

    <form method="POST" action="{{ route('kardex.resguardo.guardar') }}">
        @csrf
        <input type="hidden" name="tmp_pdf" value="{{ $tmpPdf }}">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ── Columna principal (2/3) ────────────────────────────── --}}
            <div class="lg:col-span-2 space-y-4">

                {{-- Tipo de equipo --}}
                <div class="bg-canvas border border-border rounded-2xl shadow-sm p-6">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-4">Tipo de equipo</h3>
                    <div class="flex gap-3">
                        @foreach(['Laptop', 'PC Avanzada', 'PC Especializada'] as $t)
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="tipo" value="{{ $t }}" class="sr-only peer"
                                {{ old('tipo', $datos['tipo']) === $t ? 'checked' : '' }}>
                            <div class="text-center border-2 border-border rounded-xl py-3 px-2 text-sm font-semibold text-on-surface-variant
                                        peer-checked:border-primary-container peer-checked:text-primary-container peer-checked:bg-surface-low transition-all">
                                {{ $t }}
                            </div>
                        </label>
                        @endforeach
                    </div>
                    @error('tipo')<p class="mt-2 text-xs text-error">{{ $message }}</p>@enderror
                </div>

                {{-- CPU --}}
                <div class="bg-canvas border border-border rounded-2xl shadow-sm p-6">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-4">CPU / Equipo principal</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Marca</label>
                            <input type="text" name="cpu_marca" value="{{ old('cpu_marca', $datos['cpu_marca']) }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border border-border outline-none focus:ring-2 focus:ring-primary-container
                                       {{ $datos['cpu_marca'] ? 'bg-yellow-50' : 'bg-canvas' }}">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Modelo</label>
                            <input type="text" name="cpu_modelo" value="{{ old('cpu_modelo', $datos['cpu_modelo']) }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border border-border outline-none focus:ring-2 focus:ring-primary-container
                                       {{ $datos['cpu_modelo'] ? 'bg-yellow-50' : 'bg-canvas' }}">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">
                                Número de serie @if($esNativo)<span class="text-error">*</span>@endif
                            </label>
                            <input type="text" name="cpu_serie" value="{{ old('cpu_serie', $datos['cpu_serie']) }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border outline-none focus:ring-2 focus:ring-primary-container
                                       {{ $errors->has('cpu_serie') ? 'border-error bg-error-container' : ($datos['cpu_serie'] ? 'border-border bg-yellow-50' : 'border-border bg-canvas') }}">
                            @error('cpu_serie')<p class="mt-1 text-xs text-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">No. Inventario</label>
                            <input type="text" name="num_inventario" value="{{ old('num_inventario', $datos['num_inventario']) }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border border-border outline-none focus:ring-2 focus:ring-primary-container
                                       {{ $datos['num_inventario'] ? 'bg-yellow-50' : 'bg-canvas' }}">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Consecutivo</label>
                            <input type="number" name="consecutivo" value="{{ old('consecutivo', $datos['consecutivo']) }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                        </div>
                    </div>
                </div>

                {{-- Periféricos (se muestran según tipo, controlado con JS) --}}
                <div class="bg-canvas border border-border rounded-2xl shadow-sm p-6" id="sec-laptop">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-4">Periféricos — Laptop</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Serie cargador</label>
                            <input type="text" name="cargador_serie" value="{{ old('cargador_serie') }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Docking marca</label>
                            <input type="text" name="docking_marca" value="{{ old('docking_marca') }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Docking serie</label>
                            <input type="text" name="docking_serie" value="{{ old('docking_serie') }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                        </div>
                    </div>
                </div>

                <div class="bg-canvas border border-border rounded-2xl shadow-sm p-6 hidden" id="sec-pc">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-4">Periféricos — PC</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Monitor marca</label>
                            <input type="text" name="monitor_marca" value="{{ old('monitor_marca') }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Monitor serie</label>
                            <input type="text" name="monitor_serie" value="{{ old('monitor_serie') }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Teclado serie</label>
                            <input type="text" name="teclado_serie" value="{{ old('teclado_serie') }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Mouse serie</label>
                            <input type="text" name="mouse_serie" value="{{ old('mouse_serie') }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Nobreak marca</label>
                            <input type="text" name="nobreak_marca" value="{{ old('nobreak_marca') }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Nobreak serie</label>
                            <input type="text" name="nobreak_serie" value="{{ old('nobreak_serie') }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                        </div>
                    </div>
                </div>

                {{-- Observaciones --}}
                <div class="bg-canvas border border-border rounded-2xl shadow-sm p-6">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-4">Observaciones</h3>
                    <textarea name="observaciones" rows="3"
                        class="w-full rounded-lg px-3 py-2 text-sm border border-border outline-none focus:ring-2 focus:ring-primary-container resize-none
                               {{ $datos['observaciones'] ? 'bg-yellow-50' : 'bg-canvas' }}">{{ old('observaciones', $datos['observaciones']) }}</textarea>
                </div>

                {{-- Texto extraído (colapsado, solo si hay texto) --}}
                @if($esNativo && $textoRaw)
                <details class="bg-surface-low border border-border rounded-2xl shadow-sm">
                    <summary class="px-6 py-4 cursor-pointer text-xs font-bold uppercase tracking-wider text-on-surface-variant flex items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size:16px">article</span>
                        Texto crudo extraído del PDF
                    </summary>
                    <pre class="px-6 pb-5 text-[11px] text-on-surface-variant whitespace-pre-wrap overflow-x-auto font-mono">{{ $textoRaw }}</pre>
                </details>
                @endif

            </div>

            {{-- ── Columna lateral (1/3) ───────────────────────────────── --}}
            <div class="space-y-4">

                {{-- Responsable --}}
                <div class="bg-canvas border border-border rounded-2xl shadow-sm p-5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-3">Responsable del equipo</h3>

                    @if($datos['nombre_usuario'])
                    <div class="mb-3 px-3 py-2 bg-yellow-50 border border-yellow-200 rounded-lg text-xs text-on-surface">
                        <span class="font-bold">Extraído del PDF:</span><br>
                        {{ $datos['nombre_usuario'] }}
                    </div>
                    @endif

                    <input type="hidden" name="nombre_usuario" value="{{ $datos['nombre_usuario'] }}">

                    @if($candidatos->count())
                    <p class="text-[11px] text-on-surface-variant mb-2 font-semibold">Coincidencias en el sistema:</p>
                    @foreach($candidatos as $emp)
                    <label class="flex items-start gap-2 p-2 rounded-lg hover:bg-surface-low cursor-pointer mb-1">
                        <input type="radio" name="id_empleado" value="{{ $emp->id_empleado }}" class="mt-0.5">
                        <div>
                            <p class="text-sm font-semibold text-on-surface">{{ $emp->nombre }} {{ $emp->apellido_paterno }}</p>
                            <p class="text-[11px] text-on-surface-variant">{{ $emp->departamento ?? '—' }}</p>
                            <p class="text-[11px] text-on-surface-variant">{{ $emp->correo }}</p>
                        </div>
                    </label>
                    @endforeach
                    <label class="flex items-start gap-2 p-2 rounded-lg hover:bg-surface-low cursor-pointer mt-2 border-t border-border pt-3">
                        <input type="radio" name="id_empleado" value="" checked class="mt-0.5">
                        <div>
                            <p class="text-sm font-semibold text-on-surface">Ninguno / Sin asignar</p>
                            <p class="text-[11px] text-on-surface-variant">El equipo quedará en almacén</p>
                        </div>
                    </label>
                    @else
                    <p class="text-xs text-on-surface-variant italic">No se encontraron coincidencias. El equipo quedará sin responsable asignado.</p>
                    <input type="hidden" name="id_empleado" value="">
                    @endif
                </div>

                {{-- Red --}}
                <div class="bg-canvas border border-border rounded-2xl shadow-sm p-5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-3">Dirección IP</h3>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Área</label>
                    <input type="text" name="area" id="campo-area" value="{{ old('area', $datos['area']) }}"
                        placeholder="Ej: DRHM, DEC..."
                        class="w-full rounded-lg px-3 py-2 text-sm border border-border outline-none focus:ring-2 focus:ring-primary-container mb-3
                               {{ $datos['area'] ? 'bg-yellow-50' : 'bg-canvas' }}">

                    <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">IPv4 asignada</label>
                    <div class="flex gap-2">
                        <input type="text" name="ipv4" id="campo-ip" value="{{ old('ipv4') }}"
                            placeholder="0.0.0.0"
                            class="flex-1 rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                        <button type="button" id="btn-sugerir-ip"
                            class="px-3 py-2 bg-surface-high border border-border rounded-lg text-xs font-bold text-on-surface-variant hover:bg-surface-highest transition-colors"
                            title="Sugerir primera IP libre del área">
                            <span class="material-symbols-outlined" style="font-size:16px">auto_fix_high</span>
                        </button>
                    </div>
                    <p id="ip-msg" class="mt-1 text-[11px] text-on-surface-variant hidden"></p>
                </div>

                {{-- Guardar --}}
                <div class="bg-canvas border border-border rounded-2xl shadow-sm p-5 flex flex-col gap-3">
                    <button type="submit"
                        class="w-full py-2.5 bg-primary-container text-on-primary text-sm font-bold rounded-lg
                               hover:opacity-90 active:scale-95 transition-all flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">save</span>
                        Guardar equipo
                    </button>
                    <a href="{{ route('kardex.resguardo.subir') }}"
                        class="w-full py-2.5 text-center text-sm font-bold text-on-surface-variant hover:bg-surface-low rounded-lg transition-colors">
                        Cancelar
                    </a>
                </div>

            </div>
        </div>
    </form>
</div>

<script>
// Mostrar/ocultar sección de periféricos según tipo
document.querySelectorAll('input[name="tipo"]').forEach(r => {
    r.addEventListener('change', function () {
        const esLaptop = this.value === 'Laptop';
        document.getElementById('sec-laptop').classList.toggle('hidden', !esLaptop);
        document.getElementById('sec-pc').classList.toggle('hidden', esLaptop);
    });
});
// Disparar al cargar según valor inicial
const tipoInicial = document.querySelector('input[name="tipo"]:checked')?.value;
if (tipoInicial) {
    const esLaptop = tipoInicial === 'Laptop';
    document.getElementById('sec-laptop').classList.toggle('hidden', !esLaptop);
    document.getElementById('sec-pc').classList.toggle('hidden', esLaptop);
}

// Sugerir IP libre
document.getElementById('btn-sugerir-ip').addEventListener('click', function () {
    const area = document.getElementById('campo-area').value.trim();
    const msg  = document.getElementById('ip-msg');
    if (!area) { msg.textContent = 'Ingresa el área primero.'; msg.classList.remove('hidden'); return; }

    fetch(`{{ route('kardex.resguardo.ip') }}?area=${encodeURIComponent(area)}`)
        .then(r => r.json())
        .then(data => {
            if (data.ip) {
                document.getElementById('campo-ip').value = data.ip;
                msg.textContent = 'IP sugerida: primera libre del rango del área.';
            } else {
                msg.textContent = data.mensaje ?? 'Sin IPs disponibles para esa área.';
            }
            msg.classList.remove('hidden');
        });
});
</script>
</x-layouts.app>
