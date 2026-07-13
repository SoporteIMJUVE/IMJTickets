<x-layouts.app title="Mi Perfil — IMJUVE CRM">
<div class="p-8 flex items-center justify-center h-96">
    <div class="text-center text-[#544246]">
        <span class="material-symbols-outlined text-5xl block mb-3">construction</span>
        <h3 class="text-xl font-bold text-[#621132] mb-1">Módulo en construcción</h3>
        <p class="text-sm mb-4">Esta sección estará disponible próximamente.</p>

        @if(session('success'))
        <p class="text-sm font-semibold mb-3" style="color:var(--color-status-active)">{{ session('success') }}</p>
        @endif

        <button type="button" onclick="document.getElementById('modal-cambiar-password').classList.remove('hidden'); document.getElementById('modal-cambiar-password').classList.add('flex');"
                class="text-sm font-bold text-brand hover:underline">
            Cambiar mi contraseña
        </button>
    </div>
</div>

<div id="modal-cambiar-password" class="fixed inset-0 z-50 hidden items-center justify-center" style="background:rgba(0,0,0,.35)">
    <div class="bg-canvas rounded-2xl shadow-xl w-full max-w-sm mx-4 overflow-hidden">
        <div class="px-6 py-5 border-b border-border">
            <h3 class="font-bold text-base text-ink">Cambiar contraseña</h3>
        </div>
        <form method="POST" action="{{ route('perfil.password') }}" class="px-6 py-5 space-y-4">
            @csrf
            @error('codigo_recuperacion')
            <p class="text-xs text-error font-semibold">{{ $message }}</p>
            @enderror
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Código de recuperación</label>
                <input type="text" name="codigo_recuperacion" required
                       placeholder="El de tu PDF, 64 caracteres"
                       class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-brand font-mono">
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Contraseña nueva</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-brand">
            </div>
            <div>
                <label class="block text-[11px] font-bold uppercase tracking-wider text-muted mb-1">Confirmar contraseña</label>
                <input type="password" name="password_confirmation" required minlength="8"
                       class="w-full rounded-lg px-3 py-2 text-sm border border-border bg-canvas outline-none focus:ring-2 focus:ring-brand">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="document.getElementById('modal-cambiar-password').classList.add('hidden'); document.getElementById('modal-cambiar-password').classList.remove('flex');"
                        class="px-4 py-2 border border-border rounded-lg text-sm font-bold text-muted hover:bg-wash transition-colors">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand text-white rounded-lg text-sm font-bold hover:opacity-90 transition-colors">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>
@if($errors->any())
<script>
document.getElementById('modal-cambiar-password').classList.remove('hidden');
document.getElementById('modal-cambiar-password').classList.add('flex');
</script>
@endif
</x-layouts.app>
