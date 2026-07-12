<x-layouts.app title="Nuevo Ticket — IMJUVE CRM">
<div class="p-8 max-w-2xl mx-auto">

    <div class="flex items-center gap-4 mb-8">
        <a href="{{ route('tickets.index') }}"
           class="p-2 rounded-lg hover:bg-surface-high transition-colors text-muted hover:text-brand">
            <span class="material-symbols-outlined">arrow_back</span>
        </a>
        <div>
            <h2 class="text-[28px] font-bold leading-tight tracking-tight text-brand">Nuevo Ticket</h2>
            <p class="text-muted text-sm mt-0.5">Registra una incidencia de soporte técnico.</p>
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
        <div class="bg-canvas border border-border rounded-2xl shadow-sm overflow-hidden">

            {{-- Solicitante auto-detectado --}}
            <div class="px-6 pt-6 pb-5 border-b border-wash flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-gold/40 flex items-center justify-center text-brand font-bold text-sm shrink-0">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}
                </div>
                <div>
                    <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Solicitante</p>
                    <p class="text-sm font-semibold text-ink">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-muted">{{ $autofillCorreo }}</p>
                </div>
            </div>
            <input type="hidden" name="correo" value="{{ $autofillCorreo }}">

            {{-- Tipo --}}
            <div class="px-6 pt-5 pb-5 border-b border-wash">
                <label for="tipo" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-2">
                    Tipo de incidente
                </label>
                <select id="tipo" name="tipo"
                    class="w-full rounded-lg px-4 py-3 text-sm border outline-none transition-colors appearance-none
                           focus:ring-2 focus:ring-brand focus:border-transparent
                           {{ $errors->has('tipo') ? 'border-red-400 bg-red-50' : 'border-border bg-[#F9F9F8]' }}">
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
            <div class="px-6 pt-5 pb-5 border-b border-wash">
                <label for="area" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-2">
                    Área donde ocurrió el incidente
                </label>
                <select id="area" name="area"
                    class="w-full rounded-lg px-4 py-3 text-sm border outline-none transition-colors appearance-none
                           focus:ring-2 focus:ring-brand focus:border-transparent
                           {{ $errors->has('area') ? 'border-red-400 bg-red-50' : 'border-border bg-[#F9F9F8]' }}">
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
                <label for="descripcion" class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-2">
                    Descripción del problema
                </label>
                <textarea id="descripcion" name="descripcion" rows="5"
                    placeholder="Describe brevemente el problema que estás experimentando…"
                    class="w-full rounded-lg px-4 py-3 text-sm border outline-none transition-colors resize-none
                           focus:ring-2 focus:ring-brand focus:border-transparent
                           {{ $errors->has('descripcion') ? 'border-red-400 bg-red-50' : 'border-border bg-[#F9F9F8]' }}">{{ old('descripcion') }}</textarea>
                @error('descripcion')
                <p class="mt-1.5 text-xs text-red-600 flex items-center gap-1">
                    <span class="material-symbols-outlined" style="font-size:14px">error</span>
                    {{ $message }}
                </p>
                @enderror
                <p class="mt-1.5 text-xs text-muted">No incluyas contraseñas ni solicitudes de altas/bajas de cuentas.</p>
            </div>
        </div>

        <input type="hidden" name="_ip"  value="{{ $ip }}">
        <input type="hidden" name="_mac" value="{{ $mac ?? '' }}">

        <div class="mt-6 flex items-center justify-end gap-3">
            <div class="flex items-center gap-3">
                <a href="{{ route('tickets.index') }}"
                   class="px-5 py-2.5 rounded-lg text-sm font-bold text-muted hover:bg-surface-high transition-colors">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-brand text-white text-sm font-bold rounded-lg
                               hover:opacity-90 active:scale-95 transition-all flex items-center gap-2">
                    <span class="material-symbols-outlined text-sm">send</span>
                    Enviar ticket
                </button>
            </div>
        </div>
    </form>
</div>
</x-layouts.app>
