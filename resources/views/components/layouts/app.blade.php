@props(['title' => null])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'IMJUVE CRM' }}</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        body { font-family: 'Inter', sans-serif; background-color: var(--color-surface); overflow: hidden; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--color-gold); border-radius: 10px; }
        .detail-panel { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .detail-panel.closed { transform: translateX(100%); }
    </style>
    <script>
        (function () {
            const saved = localStorage.getItem('imj-theme');
            const sys   = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.dataset.theme = saved ?? sys;
        })();
    </script>
</head>
<body class="flex text-ink">

{{-- Sidebar 280px — white/light with gold right border --}}
<aside class="fixed left-0 top-0 h-screen w-[280px] bg-surface border-r border-gold flex flex-col overflow-y-auto px-4 py-6 z-50" id="sidebar">

    {{-- Brand --}}
    <div class="mb-8 flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-gold flex items-center justify-center overflow-hidden">
            <img src="/images/IMJCabezaT.png" alt="IMJUVE" class="w-8 h-8 object-contain">
        </div>
        <div>
            <h2 class="font-bold text-lg leading-none text-brand">IMJUVE - Sistemas</h2>
            <p class="text-[11px] text-muted">IT Administration</p>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 space-y-1">
        @if(auth()->user()?->isAdmin())
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 px-4 py-3 rounded text-sm transition-colors
                      {{ request()->routeIs('dashboard') ? 'text-brand font-bold border-r-4 border-brand bg-surface-high' : 'text-muted hover:bg-wash' }}">
                <span class="material-symbols-outlined">dashboard</span>
                Dashboard
            </a>

            <a href="{{ route('crm.index') }}"
               class="flex items-center gap-3 px-4 py-3 rounded text-sm transition-colors
                      {{ request()->routeIs('crm.*') ? 'text-brand font-bold border-r-4 border-brand bg-surface-high' : 'text-muted hover:bg-wash' }}">
                <span class="material-symbols-outlined">group</span>
                Usuarios
            </a>

            <a href="{{ route('network.index') }}"
               class="flex items-center gap-3 px-4 py-3 rounded text-sm transition-colors
                      {{ request()->routeIs('network.*') ? 'text-brand font-bold border-r-4 border-brand bg-surface-high' : 'text-muted hover:bg-wash' }}">
                <span class="material-symbols-outlined">lan</span>
                Red e IPs
            </a>

            <a href="{{ route('kardex.index') }}"
               class="flex items-center gap-3 px-4 py-3 rounded text-sm transition-colors
                      {{ request()->routeIs('kardex.*') ? 'text-brand font-bold border-r-4 border-brand bg-surface-high' : 'text-muted hover:bg-wash' }}">
                <span class="material-symbols-outlined">inventory_2</span>
                Inventario
            </a>

            <a href="{{ route('tickets.index') }}"
               class="flex items-center gap-3 px-4 py-3 rounded text-sm transition-colors
                      {{ request()->routeIs('tickets.*') ? 'text-brand font-bold border-r-4 border-brand bg-surface-high' : 'text-muted hover:bg-wash' }}">
                <span class="material-symbols-outlined">description</span>
                Tickets
                @php try { $open = \Modules\Tickets\Models\Ticket::where('estado', 0)->count(); } catch (\Throwable $e) { $open = 0; } @endphp
                @if($open > 0)
                    <span class="ml-auto text-[10px] font-bold bg-brand text-white px-2 py-0.5 rounded-full">{{ $open }}</span>
                @endif
            </a>
        @else
            <a href="{{ route('perfil') }}"
               class="flex items-center gap-3 px-4 py-3 rounded text-sm transition-colors
                      {{ request()->routeIs('perfil') ? 'text-brand font-bold border-r-4 border-brand bg-surface-high' : 'text-muted hover:bg-wash' }}">
                <span class="material-symbols-outlined">person</span>
                Perfil
            </a>

            <a href="{{ route('tickets.create') }}"
               class="flex items-center gap-3 px-4 py-3 rounded text-sm transition-colors
                      {{ request()->routeIs('tickets.create') ? 'text-brand font-bold border-r-4 border-brand bg-surface-high' : 'text-muted hover:bg-wash' }}">
                <span class="material-symbols-outlined">confirmation_number</span>
                Nuevo Ticket
            </a>
        @endif
    </nav>

    {{-- Footer --}}
    <div class="mt-auto pt-6 border-t border-border space-y-1">
        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded text-sm text-muted hover:bg-wash transition-colors">
            <span class="material-symbols-outlined">settings</span>
            Configuración
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded text-sm text-muted hover:bg-wash transition-colors text-left">
                <span class="material-symbols-outlined">logout</span>
                Cerrar sesión
            </button>
        </form>
        <div class="px-4 py-3 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-gold flex items-center justify-center text-brand font-bold text-xs">
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 2)) }}
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-ink truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                <p class="text-[10px] text-muted uppercase tracking-wider">{{ auth()->user()->role ?? 'Sistemas IT' }}</p>
            </div>
        </div>
    </div>
</aside>

{{-- Main content area --}}
<main class="ml-[280px] flex-1 flex flex-col h-screen overflow-hidden">

    {{-- TopBar --}}
    <header class="h-16 bg-canvas border-b border-border shadow-sm flex justify-between items-center px-8 z-40 sticky top-0">
        <div class="flex items-center flex-1 max-w-xl">
            @if(auth()->user()?->isAdmin())
                <form id="global-search-form" action="{{ route('dashboard') }}" method="GET"
                      class="relative w-full" onsubmit="submitSearch(event)">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-muted pointer-events-none">search</span>
                    <input id="global-search"
                           name="q"
                           value="{{ request('q') }}"
                           class="w-full bg-wash border border-transparent rounded-lg pl-10 pr-10 py-2 text-sm focus:ring-2 focus:ring-brand focus:border-brand outline-none transition-all"
                           placeholder="Buscar en todo el sistema… (Enter)"
                           type="text"
                           autocomplete="off">
                    <button type="submit"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-muted hover:text-brand transition-colors">
                        <span class="material-symbols-outlined text-sm">arrow_forward</span>
                    </button>
                </form>
            @endif
        </div>
        <div class="flex items-center gap-6 ml-6">
            <div class="flex items-center gap-4">
                <button id="theme-toggle" onclick="toggleTheme()"
                        class="relative p-1 text-muted hover:text-brand transition-all cursor-pointer active:scale-95"
                        title="Cambiar tema">
                    <span class="material-symbols-outlined" id="theme-icon">light_mode</span>
                </button>
                <button class="relative p-1 text-muted hover:text-brand transition-all cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined">notifications</span>
                </button>
                <button class="p-1 text-muted hover:text-brand transition-all cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined">history</span>
                </button>
                <button class="p-1 text-muted hover:text-brand transition-all cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined">help</span>
                </button>
            </div>
            <div class="h-8 w-px bg-border"></div>
            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold uppercase tracking-wide text-brand leading-none">{{ strtoupper(auth()->user()->name ?? 'ADMINISTRATOR') }}</p>
                    <p class="text-[10px] text-muted">{{ auth()->user()->role ?? 'Sistemas IT' }}</p>
                </div>
                <div class="w-10 h-10 rounded-full border border-gold bg-gold/30 flex items-center justify-center text-brand font-bold text-sm">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 2)) }}
                </div>
            </div>
        </div>
    </header>

    {{-- Page content --}}
    <div class="flex-1 overflow-auto custom-scrollbar">
        {{ $slot }}
    </div>
</main>

<x-error-button />
@livewireScripts
<script>
// ── Búsqueda global — Enter navega al dashboard con ?q= ──────────────────────
function submitSearch(e) {
    e.preventDefault();
    const q = document.getElementById('global-search')?.value.trim();
    if (!q) return;
    window.location.href = '{{ route("dashboard") }}?q=' + encodeURIComponent(q);
}

// Ctrl+K o / para enfocar el buscador desde cualquier página
document.addEventListener('keydown', function (e) {
    if ((e.key === 'k' && (e.ctrlKey || e.metaKey))) {
        e.preventDefault();
        document.getElementById('global-search')?.focus();
    }
});

// ── Toggle de tema ───────────────────────────────────────────────────────────
function toggleTheme() {
    const root    = document.documentElement;
    const current = root.dataset.theme ?? 'light';
    const next    = current === 'dark' ? 'light' : 'dark';
    root.dataset.theme = next;
    localStorage.setItem('imj-theme', next);
    document.getElementById('theme-icon').textContent = next === 'dark' ? 'dark_mode' : 'light_mode';
}
// Sync icon on load
(function () {
    const t = document.documentElement.dataset.theme;
    const el = document.getElementById('theme-icon');
    if (el) el.textContent = t === 'dark' ? 'dark_mode' : 'light_mode';
})();
</script>
</body>
</html>
