<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportar incidencia — IMJUVE</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; background: #f4f1ec; min-height: 100vh; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
    </style>
</head>
<body class="flex items-start justify-center py-12 px-4">

<div class="w-full max-w-xl">

    {{-- Encabezado --}}
    <div class="flex items-center gap-4 mb-8">
        <div class="w-10 h-10 rounded-lg bg-[#621132] flex items-center justify-center shrink-0">
            <span class="material-symbols-outlined text-white" style="font-variation-settings:'FILL' 1">account_balance</span>
        </div>
        <div>
            <h2 class="text-[26px] font-bold leading-tight tracking-tight text-[#621132]">Reportar incidencia</h2>
            <p class="text-[#544246] text-sm mt-0.5">Soporte Técnico — Instituto Mexicano de la Juventud</p>
        </div>
    </div>

    @if(session('success'))
    <div class="mb-6 flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm font-semibold">
        <span class="material-symbols-outlined text-green-600">check_circle</span>
        {{ session('success') }}
    </div>
    @endif

    <form method="POST" action="{{ route('tickets.store') }}" novalidate>
        @csrf
        <div class="bg-white border border-[#E5E7EB] rounded-2xl shadow-sm overflow-hidden">

            {{-- Correo --}}
            <div class="px-6 pt-6 pb-5 border-b border-[#F3F4F6]">
                <label for="correo" class="block text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-2">
                    Correo institucional
                </label>
                <input id="correo" name="correo" type="email"
                    value="{{ old('correo') }}"
                    placeholder="nombre.apellido@imjuve.gob.mx"
                    class="w-full rounded-lg px-4 py-3 text-sm border outline-none transition-colors
                           focus:ring-2 focus:ring-[#621132] focus:border-transparent
                           {{ $errors->has('correo') ? 'border-red-400 bg-red-50' : 'border-[#E5E7EB] bg-[#F9F9F8]' }}">
                @error('correo')
                <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size:14px">error</span>
                    {{ $message }}
                </p>
                @enderror
            </div>

            {{-- Tipo --}}
            <div class="px-6 pt-5 pb-5 border-b border-[#F3F4F6]">
                <label for="tipo" class="block text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-2">
                    Tipo de incidente
                </label>
                <select id="tipo" name="tipo"
                    class="w-full rounded-lg px-4 py-3 text-sm border outline-none transition-colors appearance-none
                           focus:ring-2 focus:ring-[#621132] focus:border-transparent
                           {{ $errors->has('tipo') ? 'border-red-400 bg-red-50' : 'border-[#E5E7EB] bg-[#F9F9F8]' }}">
                    <option value="" disabled {{ old('tipo') ? '' : 'selected' }}>Selecciona el tipo de incidente…</option>
                    @foreach($tipos as $t)
                    <option value="{{ $t }}" {{ old('tipo') === $t ? 'selected' : '' }}>{{ $t }}</option>
                    @endforeach
                </select>
                @error('tipo')
                <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size:14px">error</span>
                    {{ $message }}
                </p>
                @enderror
            </div>

            {{-- Área --}}
            <div class="px-6 pt-5 pb-5 border-b border-[#F3F4F6]">
                <label for="area" class="block text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-2">
                    Área donde ocurrió el incidente
                </label>
                <select id="area" name="area"
                    class="w-full rounded-lg px-4 py-3 text-sm border outline-none transition-colors appearance-none
                           focus:ring-2 focus:ring-[#621132] focus:border-transparent
                           {{ $errors->has('area') ? 'border-red-400 bg-red-50' : 'border-[#E5E7EB] bg-[#F9F9F8]' }}">
                    <option value="" disabled {{ old('area') ? '' : 'selected' }}>Selecciona el área…</option>
                    @foreach($areas as $a)
                    <option value="{{ $a }}" {{ old('area') === $a ? 'selected' : '' }}>{{ $a }}</option>
                    @endforeach
                </select>
                @error('area')
                <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size:14px">error</span>
                    {{ $message }}
                </p>
                @enderror
            </div>

            {{-- Descripción --}}
            <div class="px-6 pt-5 pb-6">
                <label for="descripcion" class="block text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-2">
                    Descripción del problema
                </label>
                <textarea id="descripcion" name="descripcion" rows="5"
                    placeholder="Describe brevemente el problema que estás experimentando…"
                    class="w-full rounded-lg px-4 py-3 text-sm border outline-none transition-colors resize-none
                           focus:ring-2 focus:ring-[#621132] focus:border-transparent
                           {{ $errors->has('descripcion') ? 'border-red-400 bg-red-50' : 'border-[#E5E7EB] bg-[#F9F9F8]' }}">{{ old('descripcion') }}</textarea>
                @error('descripcion')
                <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size:14px">error</span>
                    {{ $message }}
                </p>
                @enderror
                <p class="mt-1.5 text-xs text-[#544246]">No incluyas contraseñas ni solicitudes de altas/bajas de cuentas.</p>
            </div>
        </div>

        <input type="hidden" name="_ip"  value="{{ $ip }}">
        <input type="hidden" name="_mac" value="{{ $mac ?? '' }}">

        <div class="mt-6 flex justify-end">
            <button type="submit"
                    class="px-6 py-2.5 bg-[#621132] text-white text-sm font-bold rounded-lg
                           hover:opacity-90 active:scale-95 transition-all flex items-center gap-2">
                <span class="material-symbols-outlined text-sm">send</span>
                Enviar ticket
            </button>
        </div>
    </form>

    <p class="text-center text-xs text-[#544246] mt-6">
        ¿Eres personal de TI?
        <a href="{{ route('login') }}" class="text-[#621132] font-semibold hover:underline">Iniciar sesión</a>
    </p>
</div>

@if($errors->any())
<x-error-button />
@endif

</body>
</html>
