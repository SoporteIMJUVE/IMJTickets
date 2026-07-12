<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportar incidencia — IMJUVE</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; min-height: 100vh; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
    </style>
    {{-- Detectar tema antes del primer paint --}}
    <script>
    (function () {
        const saved = localStorage.getItem('imj-theme');
        const sys   = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.dataset.theme = saved ?? sys;
    })();
    </script>
</head>
<body class="bg-surface flex items-start justify-center py-12 px-4">

<div class="w-full max-w-xl">

    {{-- Encabezado con logo --}}
    <div class="flex items-center gap-4 mb-8">
        <div class="w-12 h-12 rounded-xl shrink-0 overflow-hidden flex items-center justify-center"
             style="background:var(--color-gold)">
            <img src="/images/IMJCabezaT.png" alt="IMJUVE" class="w-10 h-10 object-contain">
        </div>
        <div>
            <h2 class="text-[26px] font-bold leading-tight tracking-tight text-brand">Reportar incidencia</h2>
            <p class="text-muted text-sm mt-0.5">Soporte Técnico — Instituto Mexicano de la Juventud</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-semibold"
         style="background:var(--color-status-closed-bg,#DCFCE7);color:var(--color-status-active);border:1px solid var(--color-status-active)30">
        <span class="material-symbols-outlined" style="color:var(--color-status-active)">check_circle</span>
        {{ session('success') }}
    </div>
    @endif

    <form method="POST" action="{{ route('tickets.store') }}" novalidate>
        @csrf
        <div class="bg-canvas border border-border rounded-2xl shadow-sm overflow-hidden">

            {{-- Nombre --}}
            <div class="px-6 pt-6 pb-5 border-b border-border">
                <label for="nombre" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-2">
                    Nombre completo
                </label>
                <input id="nombre" name="nombre" type="text"
                    value="{{ old('nombre') }}"
                    placeholder="Tu nombre completo"
                    class="w-full rounded-lg px-4 py-3 text-sm border outline-none transition-colors
                           bg-wash text-ink focus:ring-2 focus:ring-brand focus:border-brand
                           {{ $errors->has('nombre') ? 'border-red-400' : 'border-border' }}">
                @error('nombre')
                <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size:14px">error</span>
                    {{ $message }}
                </p>
                @enderror
            </div>

            {{-- Correo --}}
            <div class="px-6 pt-5 pb-5 border-b border-border">
                <label for="correo" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-2">
                    Correo institucional
                </label>
                <input id="correo" name="correo" type="email"
                    value="{{ old('correo') }}"
                    placeholder="nombre.apellido@imjuventud.gob.mx"
                    class="w-full rounded-lg px-4 py-3 text-sm border outline-none transition-colors
                           bg-wash text-ink focus:ring-2 focus:ring-brand focus:border-brand
                           {{ $errors->has('correo') ? 'border-red-400' : 'border-border' }}">
                @error('correo')
                <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size:14px">error</span>
                    {{ $message }}
                </p>
                @enderror
            </div>

            {{-- Tipo --}}
            <div class="px-6 pt-5 pb-5 border-b border-border">
                <label for="tipo" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-2">
                    Tipo de incidente
                </label>
                <select id="tipo" name="tipo"
                    class="w-full rounded-lg px-4 py-3 text-sm border outline-none transition-colors appearance-none
                           bg-wash text-ink focus:ring-2 focus:ring-brand focus:border-brand
                           {{ $errors->has('tipo') ? 'border-red-400' : 'border-border' }}">
                    <option value="" disabled {{ old('tipo') ? '' : 'selected' }}>Selecciona el tipo de incidente…</option>
                    @foreach($tipos as $t)
                    <option value="{{ $t }}" {{ old('tipo') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
                @error('tipo')
                <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size:14px">error</span>
                    {{ $message }}
                </p>
                @enderror
            </div>

            {{-- Área --}}
            <div class="px-6 pt-5 pb-5 border-b border-border">
                <label for="area" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-2">
                    Área donde ocurrió el incidente
                </label>
                <select id="area" name="area"
                    class="w-full rounded-lg px-4 py-3 text-sm border outline-none transition-colors appearance-none
                           bg-wash text-ink focus:ring-2 focus:ring-brand focus:border-brand
                           {{ $errors->has('area') ? 'border-red-400' : 'border-border' }}">
                    <option value="" disabled {{ old('area') ? '' : 'selected' }}>Selecciona el área…</option>
                    @foreach($areas as $a)
                    <option value="{{ $a }}" {{ old('area') === $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
                @error('area')
                <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size:14px">error</span>
                    {{ $message }}
                </p>
                @enderror
            </div>

            {{-- Descripción --}}
            <div class="px-6 pt-5 pb-6">
                <label for="descripcion" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-2">
                    Descripción del problema
                </label>
                <textarea id="descripcion" name="descripcion" rows="5"
                    placeholder="Describe brevemente el problema que estás experimentando…"
                    class="w-full rounded-lg px-4 py-3 text-sm border outline-none transition-colors resize-none
                           bg-wash text-ink focus:ring-2 focus:ring-brand focus:border-brand
                           {{ $errors->has('descripcion') ? 'border-red-400' : 'border-border' }}">{{ old('descripcion') }}</textarea>
                @error('descripcion')
                <p class="mt-1.5 text-xs text-red-500 flex items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size:14px">error</span>
                    {{ $message }}
                </p>
                @enderror
                <p class="mt-1.5 text-xs text-muted">No incluyas contraseñas ni solicitudes de altas/bajas de cuentas.</p>
            </div>
        </div>

        <input type="hidden" name="_ip"  value="{{ $ip }}">
        <input type="hidden" name="_mac" value="{{ $mac ?? '' }}">

        <div class="mt-6 flex justify-end">
            <button type="submit"
                    class="px-6 py-2.5 text-white text-sm font-bold rounded-lg
                           hover:opacity-90 active:scale-95 transition-all flex items-center gap-2"
                    style="background:var(--color-brand)">
                <span class="material-symbols-outlined text-sm">send</span>
                Enviar ticket
            </button>
        </div>
    </form>

    <p class="text-center text-xs text-muted mt-6">
        ¿Eres personal de TI?
        <a href="{{ route('login') }}" class="text-brand font-semibold hover:underline">Iniciar sesión</a>
    </p>
</div>

@if($errors->any())
<x-error-button />
@endif

</body>
</html>
