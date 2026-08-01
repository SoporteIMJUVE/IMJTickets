<x-layouts.app title="Registrar equipo — Asistente">
@php
    $esAdmin           = auth()->user()->isAdmin();
    $pasoInicial       = $errors->has('id_empleado') ? 2
        : ($errors->has('area')       ? 3
        : ($errors->has('ticket_ref') ? 4 : 1));
    $areasDisponibles  = \Illuminate\Support\Facades\DB::table('cat_rangos_ips')
        ->orderBy('area_nombre')->pluck('area_nombre')->unique()->values();
@endphp
<div class="p-6 max-w-3xl mx-auto">

    {{-- Encabezado --}}
    <div class="flex items-center gap-4 mb-6">
        <a href="{{ route('kardex.resguardo.subir') }}"
           class="p-2 rounded-lg hover:bg-surface-high transition-colors text-on-surface-variant">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="text-[28px] font-bold leading-tight tracking-tight text-primary-container">Registrar equipo</h2>
            <p class="text-on-surface-variant text-sm mt-0.5">Completa los tres pasos para crear o actualizar el resguardo.</p>
        </div>
    </div>

    {{-- ── Breadcrumb chevron (4 pasos) ───────────────────────────── --}}
    <div class="flex mb-8" style="height:56px">
        <button type="button" id="step-btn-1" onclick="irAPaso(1)"
            class="flex-1 py-2 pl-3 pr-1 text-left text-xs font-bold transition-all
                   {{ $pasoInicial > 1 ? 'bg-green-700 text-white' : 'bg-primary-container text-white' }}"
            style="clip-path:polygon(0% 0%,88% 0%,100% 50%,88% 100%,0% 100%);min-width:0">
            <span class="block text-[9px] font-normal opacity-75 uppercase tracking-wider leading-none mb-0.5">Paso 1</span>
            Datos técnicos
        </button>
        <button type="button" id="step-btn-2" onclick="irAPaso(2)"
            class="flex-1 py-2 pl-8 pr-1 text-left text-xs font-bold transition-all -ml-3
                   {{ $pasoInicial === 2 ? 'bg-primary-container text-white' : ($pasoInicial > 2 ? 'bg-green-700 text-white' : 'bg-surface-high text-muted') }}"
            style="clip-path:polygon(0% 0%,88% 0%,100% 50%,88% 100%,0% 100%,13% 50%);min-width:0">
            <span class="block text-[9px] font-normal opacity-75 uppercase tracking-wider leading-none mb-0.5">Paso 2</span>
            Responsable
        </button>
        <button type="button" id="step-btn-3" onclick="irAPaso(3)"
            class="flex-1 py-2 pl-8 pr-1 text-left text-xs font-bold transition-all -ml-3
                   {{ $pasoInicial === 3 ? 'bg-primary-container text-white' : ($pasoInicial > 3 ? 'bg-green-700 text-white' : 'bg-surface-high text-muted') }}"
            style="clip-path:polygon(0% 0%,88% 0%,100% 50%,88% 100%,0% 100%,13% 50%);min-width:0">
            <span class="block text-[9px] font-normal opacity-75 uppercase tracking-wider leading-none mb-0.5">Paso 3</span>
            Red
        </button>
        <button type="button" id="step-btn-4" onclick="irAPaso(4)"
            class="flex-1 py-2 pl-8 pr-1 text-left text-xs font-bold transition-all -ml-3
                   {{ $pasoInicial === 4 ? 'bg-primary-container text-white' : 'bg-surface-high text-muted' }}"
            style="clip-path:polygon(0% 0%,88% 0%,100% 50%,88% 100%,0% 100%,13% 50%);min-width:0">
            <span class="block text-[9px] font-normal opacity-75 uppercase tracking-wider leading-none mb-0.5">Paso 4</span>
            Ticket
        </button>
    </div>

    {{-- Banners globales --}}
    @if(!$esNativo)
    <div class="mb-4 flex items-start gap-3 bg-yellow-100 border border-yellow-300 text-on-surface rounded-xl px-4 py-3 text-sm">
        <span class="material-symbols-outlined text-yellow-600 mt-0.5">image_search</span>
        <div>
            <p class="font-bold text-yellow-800">PDF escaneado (imagen) — ingreso manual</p>
            <p class="text-yellow-700 mt-0.5">No se detectó texto en el archivo. Llena los campos a mano.</p>
        </div>
    </div>
    @endif
    @if($errors->any())
    <div class="mb-4 flex items-start gap-3 bg-error-container border border-red-200 text-on-surface rounded-xl px-4 py-3 text-sm font-semibold">
        <span class="material-symbols-outlined text-error mt-0.5">error</span>
        <div>@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>
    </div>
    @endif

    {{-- ── Formulario ──────────────────────────────────────────────── --}}
    <form method="POST" action="{{ route('kardex.resguardo.guardar') }}">
        @csrf
        <input type="hidden" name="tmp_pdf" value="{{ $tmpPdf }}">

        {{-- ══════════════════════════════════════
             PASO 1: Datos técnicos
             ══════════════════════════════════════ --}}
        <div id="paso-1" class="{{ $pasoInicial !== 1 ? 'hidden' : '' }}">

            {{-- Tipo --}}
            <div class="bg-canvas border border-border rounded-2xl shadow-sm p-6 mb-4">
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
            <div class="bg-canvas border border-border rounded-2xl shadow-sm p-6 mb-4">
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
                        <input type="text" name="cpu_serie" id="campo-serie"
                            value="{{ old('cpu_serie', $datos['cpu_serie']) }}"
                            autocomplete="off"
                            class="w-full rounded-lg px-3 py-2 text-sm border outline-none focus:ring-2 focus:ring-primary-container
                                   {{ $errors->has('cpu_serie') ? 'border-red-400 bg-red-50' : ($datos['cpu_serie'] ? 'border-border bg-yellow-50' : 'border-border bg-canvas') }}">
                        <p id="error-serie-paso1" class="hidden mt-1 text-xs text-red-600">El número de serie es obligatorio para continuar.</p>
                        @error('cpu_serie')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        <div id="serie-info" class="hidden mt-2 px-3 py-2 bg-surface-low border border-border rounded-lg text-xs space-y-0.5">
                            <p class="font-bold text-on-surface" id="serie-info-titulo"></p>
                            <p class="text-muted" id="serie-info-resp"></p>
                        </div>
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

            {{-- Periféricos Laptop --}}
            <div class="bg-canvas border border-border rounded-2xl shadow-sm p-6 mb-4" id="sec-laptop">
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

            {{-- Periféricos PC --}}
            <div class="bg-canvas border border-border rounded-2xl shadow-sm p-6 mb-4 hidden" id="sec-pc">
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
            <div class="bg-canvas border border-border rounded-2xl shadow-sm p-6 mb-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-4">Observaciones</h3>
                <textarea name="observaciones" rows="3"
                    class="w-full rounded-lg px-3 py-2 text-sm border border-border outline-none focus:ring-2 focus:ring-primary-container resize-none
                           {{ $datos['observaciones'] ? 'bg-yellow-50' : 'bg-canvas' }}">{{ old('observaciones', $datos['observaciones']) }}</textarea>
            </div>

            {{-- Texto crudo --}}
            @if($esNativo && $textoRaw)
            <details class="bg-surface-low border border-border rounded-2xl shadow-sm mb-4">
                <summary class="px-6 py-4 cursor-pointer text-xs font-bold uppercase tracking-wider text-on-surface-variant flex items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size:16px">article</span>
                    Texto crudo extraído del PDF
                </summary>
                <pre class="px-6 pb-5 text-[11px] text-on-surface-variant whitespace-pre-wrap overflow-x-auto font-mono">{{ $textoRaw }}</pre>
            </details>
            @endif

            {{-- Nav paso 1 --}}
            <div class="flex justify-end mt-2 mb-8">
                <button type="button" onclick="irAPaso(2)"
                    class="bg-primary-container text-on-primary font-semibold py-2.5 px-10 hover:opacity-90 transition-all text-sm"
                    style="clip-path:polygon(0% 0%,85% 0%,100% 50%,85% 100%,0% 100%,15% 50%)">
                    Paso Siguiente
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════════════
             PASO 2: Responsable
             ══════════════════════════════════════ --}}
        <div id="paso-2" class="{{ $pasoInicial !== 2 ? 'hidden' : '' }}">

            {{-- Mini-resumen equipo --}}
            <div class="mb-4 flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-surface-low border border-border rounded-full text-xs font-semibold text-on-surface-variant">
                    <span class="material-symbols-outlined" style="font-size:14px">computer</span>
                    <span id="resumen-tipo">{{ old('tipo', $datos['tipo']) ?: '—' }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-surface-low border border-border rounded-full text-xs font-mono text-on-surface-variant">
                    <span class="material-symbols-outlined" style="font-size:14px">tag</span>
                    <span id="resumen-serie">{{ old('cpu_serie', $datos['cpu_serie']) ?: '—' }}</span>
                </span>
            </div>

            {{-- Banner operación Kardex --}}
            <div id="banner-operacion" class="mb-4 flex items-start gap-3 rounded-xl px-4 py-3 text-sm"></div>

            {{-- Warning nombre --}}
            <div id="warning-nombre" class="hidden mb-3 px-3 py-2.5 bg-orange-50 border border-orange-300 rounded-lg text-xs">
                <p class="font-bold text-orange-800 flex items-center gap-1 mb-1">
                    <span class="material-symbols-outlined text-sm">warning</span>
                    Posible responsable erróneo
                </p>
                <p class="text-orange-700">
                    El PDF menciona "<strong id="warning-pdf-nombre"></strong>" pero estás asignando a "<strong id="warning-sel-nombre"></strong>".
                </p>
            </div>

            {{-- Responsable --}}
            <div class="bg-canvas border border-border rounded-2xl shadow-sm p-5 mb-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-0.5 flex items-center gap-1">
                    Responsable del equipo <span class="text-red-500 font-bold">*</span>
                </h3>
                <p class="text-[11px] text-on-surface-variant mb-3">Obligatorio — todo resguardo debe tener responsable.</p>

                <input type="hidden" name="nombre_usuario"       value="{{ $datos['nombre_usuario'] }}">
                <input type="hidden" name="nombre_pdf_detectado" value="{{ $datos['nombre_usuario'] ?? '' }}">

                @if($datos['nombre_usuario'])
                <div class="mb-3 px-3 py-2 bg-yellow-50 border border-yellow-200 rounded-lg text-xs">
                    <span class="font-bold text-yellow-800">PDF detectó:</span>
                    <span class="text-on-surface ml-1">{{ $datos['nombre_usuario'] }}</span>
                </div>
                @endif

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

                <button type="button" id="btn-buscar-usuario"
                        class="w-full mt-2 py-2 border border-dashed border-border rounded-lg text-xs font-bold text-on-surface-variant hover:bg-surface-low transition-colors flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-sm">person_search</span>
                    Buscar entre todos los usuarios
                </button>

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

                <p id="error-resp-paso2" class="hidden mt-2 text-xs text-red-600 font-semibold flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">error</span>Debes seleccionar un responsable para continuar.
                </p>
                @error('id_empleado')
                <p class="mt-2 text-xs text-red-600 font-semibold flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">error</span>{{ $message }}
                </p>
                @enderror
            </div>

            {{-- Nav paso 2 --}}
            <div class="flex items-center justify-between mt-2 mb-8">
                <button type="button" onclick="irAPaso(1)"
                    class="py-2.5 px-6 text-sm font-semibold text-on-surface-variant hover:bg-surface-low rounded-lg transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>Anterior
                </button>
                <button type="button" onclick="irAPaso(3)"
                    class="bg-primary-container text-on-primary font-semibold py-2.5 px-10 hover:opacity-90 transition-all text-sm"
                    style="clip-path:polygon(0% 0%,85% 0%,100% 50%,85% 100%,0% 100%,15% 50%)">
                    Paso Siguiente
                </button>
            </div>
        </div>

        {{-- ══════════════════════════════════════
             PASO 3: Red & Guardar
             ══════════════════════════════════════ --}}
        @php $ipActual = $equipoExistente->ipv4 ?? null; @endphp
        <div id="paso-3" class="{{ $pasoInicial !== 3 ? 'hidden' : '' }}">

            {{-- Mini-resumen equipo + responsable --}}
            <div class="mb-4 flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-surface-low border border-border rounded-full text-xs font-semibold text-on-surface-variant">
                    <span class="material-symbols-outlined" style="font-size:14px">computer</span>
                    <span id="resumen2-tipo">{{ old('tipo', $datos['tipo']) ?: '—' }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-surface-low border border-border rounded-full text-xs font-mono text-on-surface-variant">
                    <span class="material-symbols-outlined" style="font-size:14px">tag</span>
                    <span id="resumen2-serie">{{ old('cpu_serie', $datos['cpu_serie']) ?: '—' }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-surface-low border border-border rounded-full text-xs font-semibold text-on-surface-variant">
                    <span class="material-symbols-outlined" style="font-size:14px">person</span>
                    <span id="resumen2-resp">Sin responsable</span>
                </span>
            </div>

            {{-- Red --}}
            <div class="bg-canvas border border-border rounded-2xl shadow-sm p-5 mb-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-3">Dirección IP</h3>
                @php $areaValorInicial = old('area', $datos['area'] ?? ($equipoExistente->area ?? '')); @endphp
                <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">
                    Área <span class="text-red-500">*</span>
                </label>
                {{-- Combobox de área: filtra cat_rangos_ips pero permite texto libre --}}
                <div class="relative mb-1" id="area-combobox">
                    <input type="hidden" name="area" id="campo-area" value="{{ $areaValorInicial }}">
                    <div class="flex gap-1">
                        <input type="text" id="campo-area-display"
                            value="{{ $areaValorInicial }}"
                            placeholder="Buscar área..."
                            autocomplete="off"
                            class="flex-1 rounded-lg px-3 py-2 text-sm border outline-none focus:ring-2 focus:ring-primary-container
                                   {{ $errors->has('area') ? 'border-red-400 bg-red-50' : ($areaValorInicial ? 'border-border bg-yellow-50' : 'border-border bg-canvas') }}"
                            oninput="areaFiltrar(this.value)"
                            onfocus="areaAbrir()"
                            onblur="areaOnBlur()"
                            onkeydown="areaTecla(event)">
                        <button type="button" onmousedown="event.preventDefault()" onclick="areaToggle()"
                            class="px-2 border border-border rounded-lg text-on-surface-variant hover:bg-surface-low transition-colors shrink-0">
                            <span class="material-symbols-outlined" style="font-size:16px">expand_more</span>
                        </button>
                    </div>
                    <div id="area-dropdown"
                         class="hidden absolute z-30 left-0 right-0 mt-1 bg-canvas border border-border rounded-xl shadow-lg overflow-hidden max-h-52 overflow-y-auto">
                    </div>
                </div>
                @error('area')<p class="mb-2 text-xs text-red-600">{{ $message }}</p>@else<div class="mb-2"></div>@enderror

                <label class="block text-[11px] font-bold uppercase tracking-wider text-on-surface-variant mb-1">IPv4 asignada</label>
                <div class="flex gap-2">
                    <input type="text" name="ipv4" id="campo-ip"
                        value="{{ old('ipv4', $ipActual) }}"
                        placeholder="Se sugiere al llenar el área"
                        readonly
                        class="flex-1 font-mono rounded-lg px-3 py-2 text-sm border border-border bg-surface-low outline-none cursor-default
                               {{ $ipActual ? 'text-brand font-bold' : 'text-on-surface-variant' }}">
                    @if(!$ipActual)
                    <button type="button" id="btn-sugerir-ip"
                        class="px-3 py-2 bg-surface-high border border-border rounded-lg text-xs font-bold text-on-surface-variant hover:bg-surface-highest transition-colors"
                        title="Sugerir primera IP libre del área">
                        <span class="material-symbols-outlined" style="font-size:16px">auto_fix_high</span>
                    </button>
                    @endif
                </div>
                @if($ipActual)
                <p class="mt-1 text-[11px] text-muted flex items-center gap-1">
                    <span class="material-symbols-outlined shrink-0" style="font-size:12px">lock</span>
                    IP actual del equipo — se conserva. Para cambiarla usa el panel de Kardex.
                </p>
                @endif
                <p id="ip-msg" class="mt-1 text-[11px] text-on-surface-variant hidden"></p>
            </div>

            {{-- Nav paso 3 --}}
            <div class="flex items-center justify-between mt-2 mb-8">
                <button type="button" onclick="irAPaso(2)"
                    class="py-2.5 px-6 text-sm font-semibold text-on-surface-variant hover:bg-surface-low rounded-lg transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>Anterior
                </button>
                <button type="button" onclick="irAPaso(4)"
                    class="bg-primary-container text-on-primary font-semibold py-2.5 px-10 hover:opacity-90 transition-all text-sm"
                    style="clip-path:polygon(0% 0%,85% 0%,100% 50%,85% 100%,0% 100%,15% 50%)">
                    Paso Siguiente
                </button>
            </div>
        </div>
        {{-- ══════════════════════════════════════
             PASO 4: Folio de Ticket
             ══════════════════════════════════════ --}}
        <div id="paso-4" class="{{ $pasoInicial !== 4 ? 'hidden' : '' }}">

            {{-- Mini-resumen equipo + responsable --}}
            <div class="mb-4 flex items-center gap-2 flex-wrap">
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-surface-low border border-border rounded-full text-xs font-semibold text-on-surface-variant">
                    <span class="material-symbols-outlined" style="font-size:14px">computer</span>
                    <span id="resumen4-tipo">{{ old('tipo', $datos['tipo']) ?: '—' }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-surface-low border border-border rounded-full text-xs font-mono text-on-surface-variant">
                    <span class="material-symbols-outlined" style="font-size:14px">tag</span>
                    <span id="resumen4-serie">{{ old('cpu_serie', $datos['cpu_serie']) ?: '—' }}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-surface-low border border-border rounded-full text-xs font-semibold text-on-surface-variant">
                    <span class="material-symbols-outlined" style="font-size:14px">person</span>
                    <span id="resumen4-resp">Sin responsable</span>
                </span>
            </div>

            {{-- Card de búsqueda --}}
            <div class="bg-canvas border border-border rounded-2xl shadow-sm p-5 mb-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-0.5 flex items-center gap-2">
                    Folio de Ticket
                    @if($esAdmin)
                    <span class="px-1.5 py-0.5 text-[10px] font-bold bg-surface-high text-muted rounded">Opcional para admin</span>
                    @else
                    <span class="px-1.5 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-700 rounded">Requerido</span>
                    @endif
                </h3>
                <p class="text-[11px] text-on-surface-variant mb-4">Solo se aceptan tickets de tipo <strong>"Solicitud de Equipo"</strong>.</p>

                <input type="hidden" name="ticket_ref" id="campo-ticket-ref" value="{{ old('ticket_ref') }}">

                <div class="flex gap-2 mb-2">
                    <input type="text" id="campo-ticket-folio"
                        value="{{ old('ticket_ref') }}"
                        placeholder="Ej: TK-2026-0008"
                        autocomplete="off"
                        class="flex-1 rounded-lg px-3 py-2 text-sm font-mono border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container"
                        oninput="this.value = this.value.toUpperCase()"
                        onkeydown="if(event.key==='Enter'){event.preventDefault();buscarTicket();}">
                    <button type="button" onclick="buscarTicket()"
                        class="px-4 py-2 bg-primary-container text-on-primary text-sm font-bold rounded-lg hover:opacity-90 transition-all flex items-center gap-1">
                        <span class="material-symbols-outlined text-sm">search</span>
                        Buscar
                    </button>
                </div>

                <p id="ticket-error" class="hidden text-xs text-red-600 font-semibold mb-3 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">error</span>
                    <span id="ticket-error-msg"></span>
                </p>
                <p id="error-ticket-paso4" class="hidden text-xs text-red-600 font-semibold mb-3 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">error</span>
                    Debes ingresar y verificar un folio de ticket para continuar.
                </p>
                @error('ticket_ref')
                <p class="text-xs text-red-600 font-semibold mb-3 flex items-center gap-1">
                    <span class="material-symbols-outlined text-sm">error</span>{{ $message }}
                </p>
                @enderror

                {{-- Preview del ticket — misma estructura que el panel de Tickets --}}
                <div id="ticket-preview" class="hidden mt-3 border border-border rounded-xl overflow-hidden">
                    {{-- Folio + Estado --}}
                    <div class="flex items-center justify-between px-4 py-3 bg-surface-low border-b border-border">
                        <span class="font-mono text-sm font-bold text-primary-container" id="tk-folio">—</span>
                        <span id="tk-estado-badge" class="px-3 py-1 rounded-full text-[11px] font-bold uppercase">—</span>
                    </div>
                    {{-- Tipo + tiempo --}}
                    <div class="flex items-center gap-2 text-xs text-muted px-4 py-2 border-b border-border">
                        <span id="tk-tipo">—</span>
                        <span class="opacity-40">·</span>
                        <span class="material-symbols-outlined" style="font-size:13px">schedule</span>
                        <span id="tk-hace">—</span>
                    </div>
                    <div class="p-4 space-y-4">
                        {{-- Información del Solicitante --}}
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-widest text-muted block mb-2">Información del Solicitante</label>
                            <div class="flex items-center gap-3 p-3 bg-surface-low rounded-xl border border-border">
                                <div class="w-10 h-10 bg-primary-container/20 rounded-full flex items-center justify-center text-primary-container font-bold text-base flex-shrink-0"
                                     id="tk-avatar">?</div>
                                <div class="min-w-0">
                                    <p class="font-bold text-sm text-on-surface" id="tk-nombre">—</p>
                                    <p class="text-xs text-muted" id="tk-correo">—</p>
                                    <p class="text-[10px] font-bold text-primary-container uppercase mt-0.5" id="tk-area">—</p>
                                </div>
                            </div>
                        </div>
                        {{-- Descripción del Incidente --}}
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-widest text-muted block mb-2">Descripción del Incidente</label>
                            <div class="p-4 bg-canvas border border-border rounded-xl text-sm leading-relaxed text-on-surface" id="tk-desc">—</div>
                        </div>
                        {{-- Datos de Red --}}
                        <div>
                            <label class="text-[10px] font-bold uppercase tracking-widest text-muted block mb-2">Datos de Red <span class="normal-case font-normal">(solo TI)</span></label>
                            <div class="bg-primary-container text-on-primary px-4 py-3 rounded-xl space-y-2 font-mono text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold opacity-60 uppercase font-sans">IP</span>
                                    <span id="tk-ip">—</span>
                                </div>
                                <div class="h-px bg-on-primary/20"></div>
                                <div class="flex justify-between items-center">
                                    <span class="text-[10px] font-bold opacity-60 uppercase font-sans">MAC</span>
                                    <span id="tk-mac">—</span>
                                </div>
                            </div>
                        </div>
                        {{-- Confirmación --}}
                        <div class="flex items-center gap-2 px-3 py-2 bg-green-50 border border-green-200 rounded-lg">
                            <span class="material-symbols-outlined text-green-600" style="font-size:16px;font-variation-settings:'FILL' 1">check_circle</span>
                            <p class="text-xs font-semibold text-green-800">Folio verificado — se vinculará al evento Kardex de este resguardo.</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Nav paso 4 --}}
            <div class="flex items-center justify-between mt-2 mb-8">
                <button type="button" onclick="irAPaso(3)"
                    class="py-2.5 px-6 text-sm font-semibold text-on-surface-variant hover:bg-surface-low rounded-lg transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">arrow_back</span>Anterior
                </button>
                <div class="flex items-center gap-3">
                    @if($esAdmin)
                    <button type="submit"
                        class="py-2.5 px-5 text-sm font-semibold text-on-surface-variant hover:bg-surface-low rounded-lg border border-border transition-colors flex items-center gap-2">
                        <span class="material-symbols-outlined text-sm">save</span>
                        Guardar sin ticket
                    </button>
                    @endif
                    <button type="submit" id="btn-guardar-con-ticket"
                        class="flex items-center gap-2 py-2.5 px-8 bg-primary-container text-on-primary text-sm font-bold rounded-lg
                               hover:opacity-90 active:scale-95 transition-all">
                        <span class="material-symbols-outlined text-sm">save</span>
                        Guardar equipo
                    </button>
                </div>
            </div>
        </div>

    </form>
</div>

{{-- Modal buscar usuario --}}
<div id="modal-buscar-usuario" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" onclick="cerrarBusquedaUsuario()"></div>
    <div class="relative bg-canvas rounded-2xl shadow-2xl w-full max-w-lg max-h-[80vh] flex flex-col border border-border">
        <div class="flex items-center justify-between px-5 py-4 border-b border-border shrink-0">
            <h3 class="font-bold text-sm text-on-surface">Buscar responsable</h3>
            <button type="button" onclick="cerrarBusquedaUsuario()" class="p-1 rounded hover:bg-surface-low text-muted">
                <span class="material-symbols-outlined text-sm">close</span>
            </button>
        </div>
        <div class="px-5 py-3 shrink-0">
            <input type="text" id="buscar-usuario-input" placeholder="Nombre, apellido o correo…" autocomplete="off"
                   class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-primary-container"
                   oninput="debounceSearch(this.value)">
        </div>
        <div id="buscar-usuario-resultados" class="overflow-y-auto flex-1 px-5 pb-4 space-y-1">
            <p class="text-xs text-muted text-center py-10">Escribe al menos 2 caracteres</p>
        </div>
    </div>
</div>

<script>
// ── Banner Kardex (definido primero porque wizard lo llama) ──────────
@php
$_eqJs = $equipoExistente ? [
    'id'          => $equipoExistente->id,
    'user_id'     => $equipoExistente->user_id,
    'responsable' => $equipoExistente->responsable_nombre,
    'tipo'        => $equipoExistente->tipo,
    'marca'       => $equipoExistente->cpu_marca,
    'modelo'      => $equipoExistente->cpu_modelo,
    'ipv4'        => $equipoExistente->ipv4,
] : null;
@endphp
const EQUIPO_EXISTENTE = @json($_eqJs);
let   EQUIPO_EXISTENTE_MUT = EQUIPO_EXISTENTE;

const BANNERS = {
    nuevo: {
        cls:'bg-green-50 border border-green-300 text-green-900',
        icon:'add_circle', iconCls:'text-green-600',
        titulo:'Equipo nuevo',
        cuerpo:'Kardex registrará: <strong>Entrada</strong> → <strong>Asignación</strong>.',
    },
    reasignacion: {
        cls:'bg-amber-50 border border-amber-300 text-amber-900',
        icon:'swap_horiz', iconCls:'text-amber-600',
        titulo:'Cambio de responsable', cuerpo:'',
    },
    renovacion: {
        cls:'bg-blue-50 border border-blue-300 text-blue-900',
        icon:'autorenew', iconCls:'text-blue-600',
        titulo:'Renovación de resguardo',
        cuerpo:'El responsable no cambia. Kardex <strong>no registrará eventos nuevos</strong> — solo se actualiza el PDF.',
    },
};

function renderBanner(tipo, nombreNuevo) {
    const b = BANNERS[tipo];
    let cuerpo = b.cuerpo;
    if (tipo === 'reasignacion') {
        const anterior = EQUIPO_EXISTENTE_MUT?.responsable ?? 'Sin responsable';
        cuerpo = `Kardex registrará: <strong>Reasignación</strong>.<br>
                  <span class="opacity-80">${anterior} → ${nombreNuevo ?? '…'}</span>`;
    }
    const banner = document.getElementById('banner-operacion');
    banner.className = `mb-4 flex items-start gap-3 rounded-xl px-4 py-3 text-sm ${b.cls}`;
    banner.innerHTML = `<span class="material-symbols-outlined ${b.iconCls} mt-0.5 shrink-0">${b.icon}</span>
        <div><p class="font-bold">${b.titulo}</p><p class="mt-0.5">${cuerpo}</p></div>`;
}

function actualizarBanner(selectedUserId, selectedNombre) {
    if (!EQUIPO_EXISTENTE_MUT) { renderBanner('nuevo', null); return; }
    if (selectedUserId && String(selectedUserId) === String(EQUIPO_EXISTENTE_MUT.user_id)) {
        renderBanner('renovacion', null);
    } else {
        renderBanner('reasignacion', selectedNombre);
    }
}

// ── Wizard ───────────────────────────────────────────────────────────
let pasoActual = {{ $pasoInicial }};
const pasosAlcanzados = new Set(Array.from({length: {{ $pasoInicial }}}, (_, i) => i + 1));

const ES_ADMIN = @json($esAdmin);

function irAPaso(destino) {
    if (destino > pasoActual) {
        if (pasoActual === 1 && !validarPaso1()) return;
        if (pasoActual === 2 && !validarPaso2()) return;
        if (pasoActual === 3 && !validarPaso3()) return;
    }
    document.getElementById(`paso-${pasoActual}`).classList.add('hidden');
    pasosAlcanzados.add(destino);
    pasoActual = destino;
    document.getElementById(`paso-${destino}`).classList.remove('hidden');
    actualizarBreadcrumb();
    actualizarResumenes();
    window.scrollTo({ top: 0, behavior: 'smooth' });
    if (destino === 2) {
        const checked = document.querySelector('input[name="id_empleado"]:checked');
        actualizarBanner(checked?.value ?? null, checked?.dataset?.nombre ?? null);
    }
}

function validarPaso1() {
    const serie = document.getElementById('campo-serie')?.value.trim();
    if (!serie || serie.length < 3) {
        document.getElementById('error-serie-paso1').classList.remove('hidden');
        document.getElementById('campo-serie').focus();
        return false;
    }
    document.getElementById('error-serie-paso1').classList.add('hidden');
    return true;
}

function validarPaso2() {
    const checked = document.querySelector('input[name="id_empleado"]:checked');
    if (!checked) {
        document.getElementById('error-resp-paso2').classList.remove('hidden');
        return false;
    }
    document.getElementById('error-resp-paso2').classList.add('hidden');
    return true;
}

function actualizarBreadcrumb() {
    [1,2,3].forEach(n => {
        const btn = document.getElementById(`step-btn-${n}`);
        if (!btn) return;
        ['bg-primary-container','bg-green-700','bg-surface-high','text-white','text-muted'].forEach(c => btn.classList.remove(c));
        if (n === pasoActual) {
            btn.classList.add('bg-primary-container','text-white');
        } else if (n < pasoActual || pasosAlcanzados.has(n)) {
            btn.classList.add('bg-green-700','text-white');
        } else {
            btn.classList.add('bg-surface-high','text-muted');
        }
    });
}

function actualizarResumenes() {
    const tipo  = document.querySelector('input[name="tipo"]:checked')?.value ?? '—';
    const serie = document.getElementById('campo-serie')?.value.trim() || '—';
    ['resumen-tipo','resumen2-tipo','resumen4-tipo'].forEach(id => { const el = document.getElementById(id); if(el) el.textContent = tipo; });
    ['resumen-serie','resumen2-serie','resumen4-serie'].forEach(id => { const el = document.getElementById(id); if(el) el.textContent = serie; });
    const checked = document.querySelector('input[name="id_empleado"]:checked');
    const respLabel = checked
        ? (checked.dataset.nombre === '__nuevo__' ? 'Nueva persona' : (checked.dataset.nombre || '—'))
        : 'Sin responsable';
    ['resumen2-resp','resumen4-resp'].forEach(id => { const el = document.getElementById(id); if(el) el.textContent = respLabel; });
}

// ── Periféricos ----------------------------------------------------------
document.querySelectorAll('input[name="tipo"]').forEach(r => {
    r.addEventListener('change', function () {
        const esLaptop = this.value === 'Laptop';
        document.getElementById('sec-laptop').classList.toggle('hidden', !esLaptop);
        document.getElementById('sec-pc').classList.toggle('hidden', esLaptop);
    });
});
(function() {
    const t = document.querySelector('input[name="tipo"]:checked')?.value;
    if (t) {
        document.getElementById('sec-laptop').classList.toggle('hidden', t !== 'Laptop');
        document.getElementById('sec-pc').classList.toggle('hidden', t === 'Laptop');
    }
})();

// ── Nombre PDF warning ---------------------------------------------------
const PDF_NOMBRE = @json($datos['nombre_usuario'] ?? '');
function checkNombreMismatch(nombreSel) {
    const banner = document.getElementById('warning-nombre');
    if (!PDF_NOMBRE || !nombreSel || nombreSel === '__nuevo__') { banner.classList.add('hidden'); return; }
    const palabras = PDF_NOMBRE.toLowerCase().split(/\s+/).filter(p => p.length > 2);
    const coincide = palabras.some(p => nombreSel.toLowerCase().includes(p));
    if (!coincide) {
        document.getElementById('warning-pdf-nombre').textContent = PDF_NOMBRE;
        document.getElementById('warning-sel-nombre').textContent = nombreSel;
        banner.classList.remove('hidden');
    } else {
        banner.classList.add('hidden');
    }
}

// ── IP del equipo actual -------------------------------------------------
const IP_EQUIPO_ACTUAL = @json($ipActual ?? null);

// ── id_empleado change handler ------------------------------------------
document.addEventListener('change', function (e) {
    if (e.target.name !== 'id_empleado') return;
    document.getElementById('form-nuevo-usuario').classList.toggle('hidden', e.target.value !== '__nuevo__');
    checkNombreMismatch(e.target.dataset.nombre ?? '');
    actualizarBanner(e.target.value, e.target.dataset.nombre ?? '');
    actualizarResumenes();

    const ipResp   = e.target.dataset.ip;
    const areaResp = e.target.dataset.area;
    const campoIp   = document.getElementById('campo-ip');
    const campoArea = document.getElementById('campo-area');
    const msg       = document.getElementById('ip-msg');

    if (ipResp && !IP_EQUIPO_ACTUAL) {
        campoIp.value = ipResp;
        msg.textContent = 'IP actual de este usuario — se reasignará a este equipo (switcheo).';
        msg.classList.remove('hidden');
    }
    if (areaResp && campoArea && !campoArea.value.trim()) {
        campoArea.value = areaResp;
        const display = document.getElementById('campo-area-display');
        if (display) display.value = areaResp;
    }
});

// ── Directores quickpick ------------------------------------------------
const dqp = document.getElementById('directores-quickpick');
if (dqp) {
    dqp.addEventListener('change', function () {
        const id = this.value;
        if (!id) return;
        const opt = this.selectedOptions[0];
        seleccionarResponsable(id, opt.dataset.nombre, opt.dataset.correo, opt.dataset.area, opt.dataset.ip,
            this.selectedOptions[0].text.split('—')[1]?.trim() ?? 'Director/Subdirector');
        this.value = '';
    });
}

function seleccionarResponsable(id, nombre, correo, area, ip, puesto) {
    const existente = document.querySelector(`input[name="id_empleado"][value="${id}"]`);
    if (existente) {
        existente.checked = true;
        existente.dispatchEvent(new Event('change', { bubbles: true }));
        existente.closest('label')?.scrollIntoView({ block: 'center', behavior: 'smooth' });
        return;
    }
    const lista = document.getElementById('lista-candidatos');
    const label = document.createElement('label');
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

// ── Modal buscar usuario ------------------------------------------------
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
    if (q.length < 2) { res.innerHTML = '<p class="text-xs text-muted text-center py-10">Escribe al menos 2 caracteres</p>'; return; }
    _searchTimer = setTimeout(() => buscarUsuarios(q), 300);
}
async function buscarUsuarios(q) {
    const res = document.getElementById('buscar-usuario-resultados');
    res.innerHTML = '<p class="text-xs text-muted text-center py-10 animate-pulse">Buscando…</p>';
    const data = await fetch(`{{ route('kardex.usuarios.buscar') }}?q=${encodeURIComponent(q)}`).then(r => r.json()).catch(() => []);
    if (!data.length) { res.innerHTML = '<p class="text-xs text-muted text-center py-10">Sin resultados.</p>'; return; }
    res.innerHTML = data.map(u => {
        const nombre = `${u.nombre} ${u.apellido_paterno}`;
        const correo = u.correo ?? ''; const depto = u.departamento ?? '—'; const puesto = u.puesto ?? '';
        const nE = nombre.replace(/'/g,"\\'"), cE = correo.replace(/'/g,"\\'"), dE = depto.replace(/'/g,"\\'"), pE = puesto.replace(/'/g,"\\'");
        return `<button type="button" class="w-full text-left px-3 py-2.5 rounded-lg hover:bg-surface-low border border-transparent hover:border-border transition-all"
                        onclick="seleccionarDesdeModal(${u.id_empleado},'${nE}','${cE}','${dE}','${pE}')">
            <p class="text-sm font-semibold text-on-surface">${nombre}</p>
            <p class="text-[11px] text-muted">${puesto}${puesto && depto ? ' · ' : ''}${depto}</p>
            <p class="text-[11px] text-muted">${correo}</p></button>`;
    }).join('');
}
function seleccionarDesdeModal(id, nombre, correo, depto, puesto) {
    cerrarBusquedaUsuario();
    seleccionarResponsable(id, nombre, correo, depto, '', puesto);
}

// ── Verificación dinámica de serie -------------------------------------
let _serieTimer = null;
document.getElementById('campo-serie').addEventListener('input', function () {
    clearTimeout(_serieTimer);
    const serie = this.value.trim().toUpperCase();
    this.value = serie;
    const infoCard = document.getElementById('serie-info');
    if (serie.length < 3) { infoCard.classList.add('hidden'); return; }
    _serieTimer = setTimeout(async () => {
        const url = `{{ route('kardex.resguardo.verificar-serie') }}?serie=${encodeURIComponent(serie)}`;
        const data = await fetch(url).then(r => r.json()).catch(() => undefined);
        EQUIPO_EXISTENTE_MUT = data ?? null;
        if (data) {
            document.getElementById('serie-info-titulo').textContent = `Equipo encontrado: ${data.tipo ?? ''} ${data.cpu_marca ?? ''} ${data.cpu_modelo ?? ''}`.trim();
            document.getElementById('serie-info-resp').textContent   = data.responsable_nombre ? `Responsable actual: ${data.responsable_nombre}` : 'Sin responsable asignado';
            infoCard.classList.remove('hidden');
        } else {
            infoCard.classList.add('hidden');
        }
        const checked = document.querySelector('input[name="id_empleado"]:checked');
        actualizarBanner(checked?.value ?? null, checked?.dataset?.nombre ?? null);
    }, 400);
});

function validarPaso3() {
    const area = document.getElementById('campo-area')?.value.trim();
    if (!area) {
        const display = document.getElementById('campo-area-display');
        if (display) { display.classList.add('border-red-400'); display.focus(); }
        return false;
    }
    const display = document.getElementById('campo-area-display');
    if (display) display.classList.remove('border-red-400');
    return true;
}

function validarPaso4() {
    if (ES_ADMIN) return true;
    const ref = document.getElementById('campo-ticket-ref')?.value.trim();
    if (!ref) {
        document.getElementById('error-ticket-paso4')?.classList.remove('hidden');
        return false;
    }
    document.getElementById('error-ticket-paso4')?.classList.add('hidden');
    return true;
}

// ── Submit guard --------------------------------------------------------
document.querySelector('form').addEventListener('submit', function (e) {
    if (!document.querySelector('input[name="id_empleado"]:checked')) {
        e.preventDefault();
        irAPaso(2);
        setTimeout(() => document.getElementById('error-resp-paso2').classList.remove('hidden'), 150);
        return;
    }
    if (!validarPaso4()) {
        e.preventDefault();
        irAPaso(4);
        setTimeout(() => document.getElementById('error-ticket-paso4')?.classList.remove('hidden'), 150);
    }
});

// ── Sugerir IP ---------------------------------------------------------
async function sugerirIp() {
    const campoArea = document.getElementById('campo-area');
    const campoIp   = document.getElementById('campo-ip');
    const msg       = document.getElementById('ip-msg');
    if (!campoArea || !campoIp) return;
    const area = campoArea.value.trim();
    if (!area) { msg.textContent = 'Ingresa el área primero.'; msg.classList.remove('hidden'); return; }
    const data = await fetch(`{{ route('kardex.resguardo.ip') }}?area=${encodeURIComponent(area)}`).then(r => r.json()).catch(() => null);
    if (data?.ip) {
        campoIp.value = data.ip;
        msg.textContent = `IP sugerida: ${data.ip} (primera libre del rango del área).`;
    } else {
        msg.textContent = data?.mensaje ?? 'Sin IPs disponibles para esa área.';
    }
    msg.classList.remove('hidden');
}
const btnSugerirIp = document.getElementById('btn-sugerir-ip');
if (btnSugerirIp) btnSugerirIp.addEventListener('click', sugerirIp);
// El blur real se maneja desde areaOnBlur() en el combobox

// ── Area combobox -------------------------------------------------------
const AREAS_DISPONIBLES = @json($areasDisponibles);
let _areaAbierto = false;
let _areaActivo  = -1;

function _areaRender(lista) {
    const dd = document.getElementById('area-dropdown');
    if (!lista.length) {
        dd.innerHTML = '<p class="px-3 py-2.5 text-xs text-muted">Sin coincidencias — se usará el texto ingresado</p>';
        return;
    }
    dd.innerHTML = lista.map((a, i) =>
        `<button type="button" data-area="${a.replace(/"/g,'&quot;')}" data-idx="${i}"
                 class="w-full text-left px-3 py-2 text-sm hover:bg-surface-low transition-colors"
                 onmousedown="event.preventDefault()" onclick="areaSeleccionar(this.dataset.area)">
             ${a}
         </button>`
    ).join('');
}

function areaFiltrar(q) {
    document.getElementById('campo-area').value = q;
    const lista = q.length === 0
        ? [...AREAS_DISPONIBLES]
        : AREAS_DISPONIBLES.filter(a => a.toLowerCase().includes(q.toLowerCase()));
    _areaRender(lista);
    document.getElementById('area-dropdown').classList.remove('hidden');
    _areaAbierto = true;
    _areaActivo  = -1;
}

function areaAbrir() {
    areaFiltrar(document.getElementById('campo-area-display').value);
}

function areaToggle() {
    _areaAbierto ? areaCerrar() : areaAbrir();
}

function areaCerrar() {
    document.getElementById('area-dropdown').classList.add('hidden');
    _areaAbierto = false;
}

function areaOnBlur() {
    // Sincronizar valor libre al hidden input
    const display = document.getElementById('campo-area-display');
    document.getElementById('campo-area').value = display.value;
    areaCerrar();
    // Sugerir IP si campo vacío
    const ci = document.getElementById('campo-ip');
    if (ci && !ci.value.trim()) sugerirIp();
}

function areaSeleccionar(valor) {
    document.getElementById('campo-area').value = valor;
    document.getElementById('campo-area-display').value = valor;
    areaCerrar();
    const ci = document.getElementById('campo-ip');
    if (ci && !ci.value.trim()) sugerirIp();
}

function areaTecla(e) {
    const items = document.getElementById('area-dropdown').querySelectorAll('button[data-idx]');
    if (e.key === 'Escape') { areaCerrar(); return; }
    if (e.key === 'Enter' && _areaActivo >= 0 && items[_areaActivo]) {
        e.preventDefault(); items[_areaActivo].click(); return;
    }
    if (e.key === 'ArrowDown') { _areaActivo = Math.min(_areaActivo + 1, items.length - 1); }
    else if (e.key === 'ArrowUp') { _areaActivo = Math.max(_areaActivo - 1, 0); }
    items.forEach((it, i) => it.classList.toggle('bg-surface-low', i === _areaActivo));
}

document.addEventListener('click', function (e) {
    const cb = document.getElementById('area-combobox');
    if (cb && !cb.contains(e.target)) areaCerrar();
});

// ── Búsqueda de ticket por folio ----------------------------------------
const _TICKET_BUSCAR_URL = '{{ route('kardex.resguardo.ticket-buscar') }}';

async function buscarTicket() {
    const inputEl  = document.getElementById('campo-ticket-folio');
    const errorEl  = document.getElementById('ticket-error');
    const errorMsg = document.getElementById('ticket-error-msg');
    const preview  = document.getElementById('ticket-preview');
    const refEl    = document.getElementById('campo-ticket-ref');

    const folio = inputEl.value.trim();
    if (!folio) return;

    errorEl.classList.add('hidden');
    preview.classList.add('hidden');
    refEl.value = '';

    const resp = await fetch(`${_TICKET_BUSCAR_URL}?folio=${encodeURIComponent(folio)}`);
    let data;
    try { data = await resp.json(); } catch { data = { error: 'Error de conexión.' }; }

    if (!resp.ok) {
        errorMsg.textContent = data.error ?? 'Error al buscar el ticket.';
        errorEl.classList.remove('hidden');
        return;
    }

    // Poblar preview (misma estructura que panel de Tickets)
    document.getElementById('tk-folio').textContent  = data.folio;
    document.getElementById('tk-nombre').textContent = data.nombre;
    document.getElementById('tk-correo').textContent = data.correo  ?? '—';
    document.getElementById('tk-area').textContent   = data.area    ?? '—';
    document.getElementById('tk-tipo').textContent   = data.tipo;
    document.getElementById('tk-hace').textContent   = data.hace;
    document.getElementById('tk-desc').textContent   = data.descripcion;
    document.getElementById('tk-ip').textContent     = data.ip  ?? 'No disponible';
    document.getElementById('tk-mac').textContent    = data.mac ?? 'No disponible';
    document.getElementById('tk-avatar').textContent = (data.nombre ?? '?')[0].toUpperCase();

    const badge = document.getElementById('tk-estado-badge');
    badge.textContent = data.estado_label;
    badge.className   = 'px-3 py-1 rounded-full text-[11px] font-bold uppercase';
    const estadoMap = {
        'Abierto':     ['bg-green-100',  'text-green-700'],
        'Atendiendo':  ['bg-amber-100',  'text-amber-700'],
        'Cerrado':     ['bg-surface-high','text-muted'],
    };
    (estadoMap[data.estado_label] ?? ['bg-surface-high','text-muted']).forEach(c => badge.classList.add(c));

    refEl.value = data.folio;
    preview.classList.remove('hidden');
    document.getElementById('error-ticket-paso4')?.classList.add('hidden');
}

// ── Init ----------------------------------------------------------------
(function () {
    actualizarBreadcrumb();
    const checked = document.querySelector('input[name="id_empleado"]:checked');
    if (checked) {
        actualizarBanner(checked.value, checked.dataset.nombre);
    } else if (EQUIPO_EXISTENTE) {
        renderBanner('reasignacion', null);
    } else {
        renderBanner('nuevo', null);
    }
})();
</script>
</x-layouts.app>
