@if($errors->any())
<div class="fixed bottom-8 right-8 z-[200]" id="error-fab-wrapper">

    {{-- Panel de errores (arriba del botón) --}}
    <div id="error-panel"
         class="absolute bottom-20 right-0 w-80 bg-canvas rounded-2xl shadow-2xl border border-red-100 overflow-hidden
                transition-all duration-200 origin-bottom-right scale-95 opacity-0 pointer-events-none"
         style="transform-origin: bottom right;">
        <div class="bg-red-50 border-b border-red-100 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-red-600" style="font-size:18px">error</span>
                <span class="font-bold text-sm text-red-800">
                    {{ $errors->count() }} {{ $errors->count() === 1 ? 'campo con error' : 'campos con errores' }}
                </span>
            </div>
            <button onclick="dismissErrorPanel()" class="text-red-400 hover:text-red-700 transition-colors">
                <span class="material-symbols-outlined" style="font-size:18px">close</span>
            </button>
        </div>
        <ul class="px-4 py-3 space-y-2 max-h-60 overflow-y-auto custom-scrollbar">
            @foreach($errors->all() as $error)
            <li class="flex items-start gap-2 text-sm text-ink">
                <span class="material-symbols-outlined text-red-400 shrink-0" style="font-size:16px;margin-top:2px">chevron_right</span>
                {{ $error }}
            </li>
            @endforeach
        </ul>
    </div>

    {{-- Botón flotante --}}
    <button onclick="toggleErrorPanel()"
            id="error-fab"
            class="relative w-14 h-14 rounded-full bg-red-600 text-white shadow-xl
                   flex items-center justify-center hover:bg-red-700 active:scale-95
                   transition-all duration-150 cursor-pointer"
            title="{{ $errors->count() }} {{ $errors->count() === 1 ? 'error en el formulario' : 'errores en el formulario' }}">
        <span class="material-symbols-outlined" style="font-variation-settings:'FILL' 1;font-size:26px">error</span>
        <span class="absolute -top-1 -right-1 w-5 h-5 rounded-full bg-canvas text-red-600 text-[10px] font-bold
                     flex items-center justify-center border-2 border-red-600">
            {{ $errors->count() }}
        </span>
        {{-- Anillo de pulso --}}
        <span class="absolute inset-0 rounded-full bg-red-500 animate-ping opacity-25 pointer-events-none"></span>
    </button>
</div>

<script>
(function () {
    var open = false;
    var panel = document.getElementById('error-panel');
    var fab   = document.getElementById('error-fab');

    window.toggleErrorPanel = function () {
        open = !open;
        if (open) {
            panel.style.pointerEvents = 'auto';
            panel.style.opacity = '1';
            panel.style.transform = 'scale(1)';
            // Detiene el pulso mientras está abierto
            fab.querySelector('.animate-ping').style.display = 'none';
        } else {
            closePanel();
        }
    };

    window.dismissErrorPanel = function () {
        open = false;
        closePanel();
    };

    function closePanel() {
        panel.style.pointerEvents = 'none';
        panel.style.opacity = '0';
        panel.style.transform = 'scale(0.95)';
        fab.querySelector('.animate-ping').style.display = '';
    }

    // Cerrar al presionar Escape
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && open) dismissErrorPanel();
    });

    // Cerrar al hacer click fuera
    document.addEventListener('click', function (e) {
        var wrapper = document.getElementById('error-fab-wrapper');
        if (open && wrapper && !wrapper.contains(e.target)) dismissErrorPanel();
    });
})();
</script>
@endif
