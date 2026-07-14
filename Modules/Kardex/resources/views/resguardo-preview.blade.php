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
                                Número de serie <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="cpu_serie" value="{{ old('cpu_serie', $datos['cpu_serie']) }}"
                                class="w-full rounded-lg px-3 py-2 text-sm border outline-none focus:ring-2 focus:ring-primary-container
                                       {{ $errors->has('cpu_serie') ? 'border-red-400 bg-red-50' : ($datos['cpu_serie'] ? 'border-border bg-yellow-50' : 'border-border bg-canvas') }}">
                            @error('cpu_serie')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
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
                    <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-0.5 flex items-center gap-1">
                        Responsable del equipo
                        <span class="text-red-500 font-bold">*</span>
                    </h3>
                    <p class="text-[11px] text-on-surface-variant mb-3">Obligatorio — todo resguardo debe tener responsable.</p>

                    <input type="hidden" name="nombre_usuario"       value="{{ $datos['nombre_usuario'] }}">
                    <input type="hidden" name="nombre_pdf_detectado" value="{{ $datos['nombre_usuario'] ?? '' }}">

                    {{-- Nombre extraído del PDF --}}
                    @if($datos['nombre_usuario'])
                    <div class="mb-3 px-3 py-2 bg-yellow-50 border border-yellow-200 rounded-lg text-xs">
                        <span class="font-bold text-yellow-800">PDF detectó:</span>
                        <span class="text-on-surface ml-1">{{ $datos['nombre_usuario'] }}</span>
                    </div>
                    @endif

                    {{-- Advertencia: responsable distinto al detectado --}}
                    <div id="warning-nombre" class="hidden mb-3 px-3 py-2.5 bg-orange-50 border border-orange-300 rounded-lg text-xs">
                        <p class="font-bold text-orange-800 flex items-center gap-1 mb-1">
                            <span class="material-symbols-outlined text-sm">warning</span>
                            Posible responsable erróneo
                        </p>
                        <p class="text-orange-700">
                            El PDF menciona "<strong id="warning-pdf-nombre"></strong>" pero estás asignando a "<strong id="warning-sel-nombre"></strong>".
                            Este ingreso quedará registrado en tu cuenta. Verifica que sea correcto.
                        </p>
                    </div>

                    {{-- Candidatos que coinciden con el nombre del PDF --}}
                    @if($candidatos->count())
                    <p class="text-[11px] font-semibold text-on-surface-variant mb-1">Coincidencias encontradas:</p>
                    @else
                    <p class="text-[11px] text-on-surface-variant italic mb-2">No se encontraron coincidencias en el PDF.</p>
                    @endif
                    <div id="lista-candidatos">
                    @foreach($candidatos as $emp)
                    <label class="flex items-start gap-2 p-2 rounded-lg hover:bg-surface-low cursor-pointer mb-1">
                        <input type="radio" name="id_empleado" value="{{ $emp->id_empleado }}" class="mt-0.5"
                               data-nombre="{{ $emp->nombre }} {{ $emp->apellido_paterno }}"
                               data-area="{{ $emp->departamento }}" data-ip="{{ $emp->ip_actual }}">
                        <div>
                            <p class="text-sm font-semibold text-on-surface">{{ $emp->nombre }} {{ $emp->apellido_paterno }}</p>
                            <p class="text-[11px] text-on-surface-variant">{{ $emp->departamento ?? '—' }}</p>
                            <p class="text-[11px] text-on-surface-variant">{{ $emp->correo }}</p>
                        </div>
                    </label>
                    @endforeach
                    </div>

                    {{-- Buscar entre todos los usuarios --}}
                    <button type="button" id="btn-buscar-usuario"
                            class="w-full mt-2 py-2 border border-dashed border-border rounded-lg text-xs font-bold text-on-surface-variant hover:bg-surface-low transition-colors flex items-center justify-center gap-2">
                        <span class="material-symbols-outlined text-sm">person_search</span>
                        Buscar entre todos los usuarios
                    </button>

                    {{-- Acceso rápido: Directores y Subdirectores --}}
                    @if($directores->count())
                    <div class="mt-3 pt-3 border-t border-border">
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Directores y Subdirectores</label>
                        <select id="directores-quickpick"
                                class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                            <option value="">Seleccionar…</option>
                            @foreach($directores as $dir)
                            <option value="{{ $dir->id_empleado }}"
                                    data-nombre="{{ $dir->nombre }} {{ $dir->apellido_paterno }}"
                                    data-correo="{{ $dir->correo }}"
                                    data-area="{{ $dir->departamento }}"
                                    data-ip="{{ $dir->ip_actual }}">
                                {{ $dir->nombre }} {{ $dir->apellido_paterno }}
                                @if($dir->puesto) — {{ $dir->puesto }}@endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    {{-- Crear nueva persona --}}
                    <div class="mt-3 pt-3 border-t border-border">
                        <label class="flex items-start gap-2 p-2 rounded-lg hover:bg-surface-low cursor-pointer">
                            <input type="radio" name="id_empleado" value="__nuevo__" id="radio-nuevo-usuario" class="mt-0.5"
                                   data-nombre="__nuevo__">
                            <div>
                                <p class="text-sm font-semibold text-on-surface">+ Crear nueva persona</p>
                                <p class="text-[11px] text-on-surface-variant">Se genera un correo de entrada temporal</p>
                            </div>
                        </label>

                        <div id="form-nuevo-usuario" class="hidden mt-2 pl-6 space-y-2 border-l-2 border-border">
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Nombre*</label>
                                <input type="text" name="nuevo_nombre" value="{{ old('nuevo_nombre') }}"
                                    class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Apellido paterno*</label>
                                    <input type="text" name="nuevo_apellido_paterno" value="{{ old('nuevo_apellido_paterno') }}"
                                        class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Apellido materno</label>
                                    <input type="text" name="nuevo_apellido_materno" value="{{ old('nuevo_apellido_materno') }}"
                                        class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Correo (opcional)</label>
                                <input type="email" name="nuevo_correo" value="{{ old('nuevo_correo') }}"
                                    placeholder="Si se deja vacío, se genera uno temporal"
                                    class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">Puesto (opcional)</label>
                                <input type="text" name="nuevo_puesto" value="{{ old('nuevo_puesto') }}"
                                    class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container">
                            </div>
                        </div>
                    </div>

                    @error('id_empleado')
                    <p class="mt-2 text-xs text-red-600 font-semibold flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">error</span>{{ $message }}
                    </p>
                    @enderror
                </div>

                {{-- Red --}}
                <div class="bg-canvas border border-border rounded-2xl shadow-sm p-5">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-3">Dirección IP</h3>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">
                        Área <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="area" id="campo-area" value="{{ old('area', $datos['area']) }}"
                        placeholder="Ej: DRHM, DEC..."
                        class="w-full rounded-lg px-3 py-2 text-sm border outline-none focus:ring-2 focus:ring-primary-container mb-1
                               {{ $errors->has('area') ? 'border-red-400 bg-red-50' : ($datos['area'] ? 'border-border bg-yellow-50' : 'border-border bg-canvas') }}">
                    @error('area')<p class="mb-2 text-xs text-red-600">{{ $message }}</p>@else<div class="mb-2"></div>@enderror

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

{{-- ── Modal: Buscar usuario ──────────────────────────────────────────── --}}
<div id="modal-buscar-usuario" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" onclick="cerrarBusquedaUsuario()"></div>
    <div class="relative bg-canvas rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col border border-border">
        <div class="flex items-center justify-between px-5 py-4 border-b border-border shrink-0">
            <h3 class="font-bold text-sm text-on-surface">Buscar responsable</h3>
            <button type="button" onclick="cerrarBusquedaUsuario()"
                    class="p-1 rounded hover:bg-surface-low text-muted">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
        <div class="px-5 py-3 shrink-0">
            <input type="text" id="buscar-usuario-input"
                   placeholder="Nombre, apellido o correo…"
                   autocomplete="off"
                   class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container"
                   oninput="debounceSearch(this.value)">
        </div>
        <div id="buscar-usuario-resultados" class="overflow-y-auto flex-1 px-5 pb-4 space-y-1">
            <p class="text-xs text-muted text-center py-10">Escribe al menos 2 caracteres</p>
        </div>
    </div>
</div>

<script>
// ── Periféricos: mostrar/ocultar según tipo ──────────────────────────
document.querySelectorAll('input[name="tipo"]').forEach(r => {
    r.addEventListener('change', function () {
        const esLaptop = this.value === 'Laptop';
        document.getElementById('sec-laptop').classList.toggle('hidden', !esLaptop);
        document.getElementById('sec-pc').classList.toggle('hidden', esLaptop);
    });
});
const tipoInicial = document.querySelector('input[name="tipo"]:checked')?.value;
if (tipoInicial) {
    document.getElementById('sec-laptop').classList.toggle('hidden', tipoInicial !== 'Laptop');
    document.getElementById('sec-pc').classList.toggle('hidden', tipoInicial === 'Laptop');
}

// ── Advertencia nombre PDF vs responsable ────────────────────────────
const PDF_NOMBRE = @json($datos['nombre_usuario'] ?? '');

function checkNombreMismatch(nombreSeleccionado) {
    const banner = document.getElementById('warning-nombre');
    if (!PDF_NOMBRE || !nombreSeleccionado || nombreSeleccionado === '__nuevo__') {
        banner.classList.add('hidden');
        return;
    }
    const palabrasPdf = PDF_NOMBRE.toLowerCase().split(/\s+/).filter(p => p.length > 2);
    const nombreNorm  = nombreSeleccionado.toLowerCase();
    const coincide    = palabrasPdf.some(p => nombreNorm.includes(p));

    if (!coincide) {
        document.getElementById('warning-pdf-nombre').textContent = PDF_NOMBRE;
        document.getElementById('warning-sel-nombre').textContent = nombreSeleccionado;
        banner.classList.remove('hidden');
    } else {
        banner.classList.add('hidden');
    }
}

// ── Event delegation: todos los radios de id_empleado ───────────────
document.addEventListener('change', function (e) {
    if (e.target.name !== 'id_empleado') return;

    document.getElementById('form-nuevo-usuario').classList.toggle('hidden', e.target.value !== '__nuevo__');

    checkNombreMismatch(e.target.dataset.nombre ?? '');

    const ipActual   = e.target.dataset.ip;
    const areaActual = e.target.dataset.area;
    const campoIp    = document.getElementById('campo-ip');
    const campoArea  = document.getElementById('campo-area');
    const msg        = document.getElementById('ip-msg');

    if (ipActual) {
        campoIp.value = ipActual;
        msg.textContent = 'IP actual de este usuario — se reasignará a este equipo (switcheo).';
        msg.classList.remove('hidden');
    }
    if (areaActual && !campoArea.value.trim()) {
        campoArea.value = areaActual;
    }
});

// ── Directores quick-pick ────────────────────────────────────────────
const directoresQuickpick = document.getElementById('directores-quickpick');
if (directoresQuickpick) {
    directoresQuickpick.addEventListener('change', function () {
        const id = this.value;
        if (!id) return;
        seleccionarResponsable(
            id,
            this.selectedOptions[0].dataset.nombre,
            this.selectedOptions[0].dataset.correo,
            this.selectedOptions[0].dataset.area,
            this.selectedOptions[0].dataset.ip,
            this.selectedOptions[0].text.split('—')[1]?.trim() ?? 'Director/Subdirector'
        );
        this.value = '';
    });
}

// ── Inyectar/activar responsable dinámicamente ───────────────────────
function seleccionarResponsable(id, nombre, correo, area, ip, puesto) {
    const existente = document.querySelector(`input[name="id_empleado"][value="${id}"]`);
    if (existente) {
        existente.checked = true;
        existente.dispatchEvent(new Event('change', { bubbles: true }));
        existente.closest('label')?.scrollIntoView({ block: 'center', behavior: 'smooth' });
        return;
    }
    const lista  = document.getElementById('lista-candidatos');
    const label  = document.createElement('label');
    label.className = 'flex items-start gap-2 p-2 rounded-lg hover:bg-surface-low cursor-pointer mb-1';
    label.innerHTML = `
        <input type="radio" name="id_empleado" value="${id}" checked class="mt-0.5"
               data-nombre="${nombre}" data-area="${area ?? ''}" data-ip="${ip ?? ''}">
        <div>
            <p class="text-sm font-semibold text-on-surface">${nombre}</p>
            <p class="text-[11px] text-on-surface-variant">${puesto ? puesto + ' · ' : ''}${area ?? '—'}</p>
            <p class="text-[11px] text-on-surface-variant">${correo ?? ''}</p>
        </div>`;
    lista.prepend(label);
    label.querySelector('input').dispatchEvent(new Event('change', { bubbles: true }));
    label.scrollIntoView({ block: 'center', behavior: 'smooth' });
}

// ── Modal buscar usuario ─────────────────────────────────────────────
let _searchTimer = null;

document.getElementById('btn-buscar-usuario').addEventListener('click', function () {
    document.getElementById('modal-buscar-usuario').classList.remove('hidden');
    setTimeout(() => document.getElementById('buscar-usuario-input').focus(), 50);
});

function cerrarBusquedaUsuario() {
    document.getElementById('modal-buscar-usuario').classList.add('hidden');
    document.getElementById('buscar-usuario-input').value = '';
    document.getElementById('buscar-usuario-resultados').innerHTML =
        '<p class="text-xs text-muted text-center py-10">Escribe al menos 2 caracteres</p>';
}

function debounceSearch(q) {
    clearTimeout(_searchTimer);
    const res = document.getElementById('buscar-usuario-resultados');
    if (q.length < 2) {
        res.innerHTML = '<p class="text-xs text-muted text-center py-10">Escribe al menos 2 caracteres</p>';
        return;
    }
    _searchTimer = setTimeout(() => buscarUsuarios(q), 300);
}

async function buscarUsuarios(q) {
    const res = document.getElementById('buscar-usuario-resultados');
    res.innerHTML = '<p class="text-xs text-muted text-center py-10 animate-pulse">Buscando…</p>';

    const data = await fetch(`{{ route('kardex.usuarios.buscar') }}?q=${encodeURIComponent(q)}`)
        .then(r => r.json()).catch(() => []);

    if (!data.length) {
        res.innerHTML = '<p class="text-xs text-muted text-center py-10">Sin resultados para esa búsqueda.</p>';
        return;
    }

    res.innerHTML = data.map(u => {
        const nombre   = `${u.nombre} ${u.apellido_paterno}`;
        const correo   = u.correo ?? '';
        const depto    = u.departamento ?? '—';
        const puesto   = u.puesto ?? '';
        const nEsc     = nombre.replace(/'/g, "\\'");
        const cEsc     = correo.replace(/'/g, "\\'");
        const dEsc     = depto.replace(/'/g, "\\'");
        const pEsc     = puesto.replace(/'/g, "\\'");
        return `<button type="button"
                        class="w-full text-left px-3 py-2.5 rounded-lg hover:bg-surface-low border border-transparent hover:border-border transition-all"
                        onclick="seleccionarDesdeModal(${u.id_empleado},'${nEsc}','${cEsc}','${dEsc}','${pEsc}')">
            <p class="text-sm font-semibold text-on-surface">${nombre}</p>
            <p class="text-[11px] text-muted">${puesto}${puesto && depto ? ' · ' : ''}${depto}</p>
            <p class="text-[11px] text-muted">${correo}</p>
        </button>`;
    }).join('');
}

function seleccionarDesdeModal(id, nombre, correo, depto, puesto) {
    cerrarBusquedaUsuario();
    seleccionarResponsable(id, nombre, correo, depto, '', puesto);
}

// ── Guard: no enviar sin responsable ────────────────────────────────
document.querySelector('form').addEventListener('submit', function (e) {
    if (!document.querySelector('input[name="id_empleado"]:checked')) {
        e.preventDefault();
        const errDiv = document.createElement('p');
        errDiv.className = 'mt-2 text-xs text-red-600 font-semibold flex items-center gap-1';
        errDiv.innerHTML = '<span class="material-symbols-outlined text-sm">error</span>Debes seleccionar un responsable antes de guardar.';
        const sec = document.querySelector('#lista-candidatos').closest('.bg-canvas');
        const existing = sec.querySelector('.error-guard');
        if (!existing) { errDiv.classList.add('error-guard'); sec.appendChild(errDiv); }
        sec.scrollIntoView({ block: 'center', behavior: 'smooth' });
    }
});

// ── Sugerir IP libre ─────────────────────────────────────────────────
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
