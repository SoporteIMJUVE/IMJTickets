@props(['title' => null])
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'IMJUVE CRM' }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #fbf9f8; overflow: hidden; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #D4C19C; border-radius: 10px; }
        .detail-panel { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .detail-panel.closed { transform: translateX(100%); }
    </style>
</head>
<body class="flex text-[#1b1c1c]">

{{-- Sidebar 280px — white/light with gold right border --}}
<aside class="fixed left-0 top-0 h-screen w-[280px] bg-[#fbf9f8] border-r border-[#D4C19C] flex flex-col overflow-y-auto px-4 py-6 z-50" id="sidebar">

    {{-- Brand --}}
    <div class="mb-8 flex items-center gap-3">
        <div class="w-10 h-10 rounded-lg bg-[#621132] flex items-center justify-center">
            <span class="material-symbols-outlined text-white" style="font-variation-settings:'FILL' 1">account_balance</span>
        </div>
        <div>
            <h2 class="font-bold text-lg leading-none text-[#621132]">IMJUVE CRM</h2>
            <p class="text-[11px] text-[#544246]">IT Administration</p>
        </div>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 space-y-1">
        <a href="{{ route('dashboard') }}"
           class="flex items-center gap-3 px-4 py-3 rounded text-sm transition-colors
                  {{ request()->routeIs('dashboard') ? 'text-[#621132] font-bold border-r-4 border-[#621132] bg-[#eae8e7]' : 'text-[#544246] hover:bg-[#F3F4F6]' }}">
            <span class="material-symbols-outlined">dashboard</span>
            Dashboard
        </a>

        <a href="{{ route('crm.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded text-sm transition-colors
                  {{ request()->routeIs('crm.*') ? 'text-[#621132] font-bold border-r-4 border-[#621132] bg-[#eae8e7]' : 'text-[#544246] hover:bg-[#F3F4F6]' }}">
            <span class="material-symbols-outlined">group</span>
            Usuarios
        </a>

        <a href="{{ route('network.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded text-sm transition-colors
                  {{ request()->routeIs('network.*') ? 'text-[#621132] font-bold border-r-4 border-[#621132] bg-[#eae8e7]' : 'text-[#544246] hover:bg-[#F3F4F6]' }}">
            <span class="material-symbols-outlined">lan</span>
            Red e IPs
        </a>

        <a href="{{ route('kardex.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded text-sm transition-colors
                  {{ request()->routeIs('kardex.*') ? 'text-[#621132] font-bold border-r-4 border-[#621132] bg-[#eae8e7]' : 'text-[#544246] hover:bg-[#F3F4F6]' }}">
            <span class="material-symbols-outlined">inventory_2</span>
            Kardex
        </a>

        <a href="{{ route('tickets.index') }}"
           class="flex items-center gap-3 px-4 py-3 rounded text-sm transition-colors
                  {{ request()->routeIs('tickets.*') ? 'text-[#621132] font-bold border-r-4 border-[#621132] bg-[#eae8e7]' : 'text-[#544246] hover:bg-[#F3F4F6]' }}">
            <span class="material-symbols-outlined">description</span>
            Tickets
            @php try { $open = \Modules\Tickets\Models\Ticket::where('estado', 0)->count(); } catch (\Throwable $e) { $open = 0; } @endphp
            @if($open > 0)
                <span class="ml-auto text-[10px] font-bold bg-[#621132] text-white px-2 py-0.5 rounded-full">{{ $open }}</span>
            @endif
        </a>
    </nav>

    {{-- Footer --}}
    <div class="mt-auto pt-6 border-t border-[#E5E7EB] space-y-1">
        <a href="#" class="flex items-center gap-3 px-4 py-3 rounded text-sm text-[#544246] hover:bg-[#F3F4F6] transition-colors">
            <span class="material-symbols-outlined">settings</span>
            Configuración
        </a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded text-sm text-[#544246] hover:bg-[#F3F4F6] transition-colors text-left">
                <span class="material-symbols-outlined">logout</span>
                Cerrar sesión
            </button>
        </form>
        <div class="px-4 py-3 flex items-center gap-3">
            <div class="w-8 h-8 rounded-full bg-[#D4C19C] flex items-center justify-center text-[#621132] font-bold text-xs">
                {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 2)) }}
            </div>
            <div class="min-w-0">
                <p class="text-xs font-bold text-[#1b1c1c] truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                <p class="text-[10px] text-[#544246] uppercase tracking-wider">{{ auth()->user()->role ?? 'Sistemas IT' }}</p>
            </div>
        </div>
    </div>
</aside>

{{-- Main content area --}}
<main class="ml-[280px] flex-1 flex flex-col h-screen overflow-hidden">

    {{-- TopBar --}}
    <header class="h-16 bg-white border-b border-[#E5E7EB] shadow-sm flex justify-between items-center px-8 z-40 sticky top-0">
        <div class="flex items-center flex-1 max-w-xl">
            <div class="relative w-full">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-[#544246]">search</span>
                <input class="w-full bg-[#F3F4F6] border-none rounded-lg pl-10 pr-4 py-2 text-sm focus:ring-2 focus:ring-[#621132] outline-none"
                       placeholder="Buscar empleados, departamentos, activos..."
                       type="text">
            </div>
        </div>
        <div class="flex items-center gap-6 ml-6">
            <div class="flex items-center gap-4">
                <button class="relative p-1 text-[#544246] hover:text-[#621132] transition-all cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined">notifications</span>
                </button>
                <button class="p-1 text-[#544246] hover:text-[#621132] transition-all cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined">history</span>
                </button>
                <button class="p-1 text-[#544246] hover:text-[#621132] transition-all cursor-pointer active:scale-95">
                    <span class="material-symbols-outlined">help</span>
                </button>
            </div>
            <div class="h-8 w-px bg-[#E5E7EB]"></div>
            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-xs font-bold uppercase tracking-wide text-[#621132] leading-none">{{ strtoupper(auth()->user()->name ?? 'ADMINISTRATOR') }}</p>
                    <p class="text-[10px] text-[#544246]">{{ auth()->user()->role ?? 'Sistemas IT' }}</p>
                </div>
                <div class="w-10 h-10 rounded-full border border-[#D4C19C] bg-[#D4C19C]/30 flex items-center justify-center text-[#621132] font-bold text-sm">
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

@livewireScripts
</body>
</html>
