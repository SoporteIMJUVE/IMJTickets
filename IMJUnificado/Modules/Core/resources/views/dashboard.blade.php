<x-layouts.app title="Dashboard — IMJUVE CRM">
<div class="p-8">

    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-[32px] font-bold leading-10 tracking-tight text-brand">Dashboard</h2>
        <p class="text-muted text-sm mt-1">Resumen operativo del sistema de TI — Instituto Mexicano de la Juventud</p>
    </div>

    {{-- ── Panel de resultados de búsqueda (solo visible con ?q) ───────────── --}}
    @if($searchResults !== null)
    <div class="mb-8 bg-canvas border border-border rounded-xl shadow-sm overflow-hidden">

        {{-- Header del panel --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-border">
            <div class="flex items-center gap-3">
                <span class="material-symbols-outlined text-brand">search</span>
                <h3 class="font-bold text-base text-ink">
                    Resultados para "<span class="text-brand">{{ $searchQuery }}</span>"
                </h3>
                @php $totalCoincidencias = collect($searchResults)->sum('total'); @endphp
                @if($totalCoincidencias > 0)
                    <span class="text-xs text-muted border border-border rounded-full px-2 py-0.5">
                        {{ $totalCoincidencias }} coincidencia{{ $totalCoincidencias !== 1 ? 's' : '' }}
                    </span>
                @endif
            </div>
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-1 text-xs text-muted hover:text-brand font-bold transition-colors">
                <span class="material-symbols-outlined text-sm">close</span>
                Limpiar búsqueda
            </a>
        </div>

        @if(count($searchResults) === 0)
            {{-- Sin resultados --}}
            <div class="py-12 text-center">
                <span class="material-symbols-outlined text-4xl text-muted block mb-3">search_off</span>
                <p class="font-bold text-ink mb-1">Sin resultados para "{{ $searchQuery }}"</p>
                <p class="text-sm text-muted">Intenta con otro término o revisa la ortografía</p>
            </div>
        @else
            {{-- Resultados en columnas (una por módulo) --}}
            <div class="grid divide-x divide-border"
                 style="grid-template-columns: repeat({{ count($searchResults) }}, 1fr)">
                @foreach($searchResults as $g)
                <div class="px-6 py-5">
                    {{-- Encabezado del módulo --}}
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-7 h-7 rounded-lg bg-brand/10 flex items-center justify-center">
                            <span class="material-symbols-outlined text-sm text-brand">{{ $g['icon'] }}</span>
                        </div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-muted">{{ $g['modulo'] }}</span>
                        <span class="ml-auto text-[10px] font-bold bg-brand/10 text-brand px-2 py-0.5 rounded-full">
                            {{ $g['total'] }}
                        </span>
                    </div>
                    {{-- Items --}}
                    <div class="space-y-0.5">
                        @foreach($g['items'] as $item)
                        <a href="{{ $item['url'] }}"
                           class="flex items-start gap-2 px-2 py-2 rounded-lg hover:bg-wash transition-colors group/r">
                            <span class="material-symbols-outlined text-sm text-muted mt-0.5 shrink-0 group-hover/r:text-brand">chevron_right</span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-ink truncate group-hover/r:text-brand">{{ $item['label'] }}</p>
                                @if(!empty($item['sub']))
                                    <p class="text-[11px] text-muted">{{ $item['sub'] }}</p>
                                @endif
                            </div>
                        </a>
                        @endforeach
                        @if($g['total'] > count($g['items']))
                        <a href="{{ $g['url'] }}"
                           class="block text-center text-[11px] text-brand font-bold pt-2 pb-1 hover:underline">
                            Ver los {{ $g['total'] }} resultados en {{ $g['modulo'] }} →
                        </a>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        @endif
    </div>
    @endif

    {{-- ── KPI Bento ────────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-12 gap-4 mb-8">
        <a href="{{ route('crm.index') }}"
           class="col-span-3 bg-canvas border border-border p-6 rounded-xl flex items-center gap-4 hover:border-gold hover:shadow-sm transition-all group">
            <div class="w-12 h-12 rounded-full bg-status-active/10 text-status-active flex items-center justify-center">
                <span class="material-symbols-outlined">group</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Usuarios Activos</p>
                <p class="text-2xl font-bold leading-tight group-hover:text-brand transition-colors">{{ $totalEmpleados }}</p>
            </div>
        </a>

        <a href="{{ route('kardex.index') }}"
           class="col-span-3 bg-canvas border border-border p-6 rounded-xl flex items-center gap-4 hover:border-gold hover:shadow-sm transition-all group">
            <div class="w-12 h-12 rounded-full bg-status-free/10 text-status-free flex items-center justify-center">
                <span class="material-symbols-outlined">computer</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Activos Registrados</p>
                <p class="text-2xl font-bold leading-tight group-hover:text-brand transition-colors">{{ $totalActivos }}</p>
            </div>
        </a>

        <a href="{{ route('tickets.index') }}"
           class="col-span-3 bg-canvas border border-border p-6 rounded-xl flex items-center gap-4 hover:border-gold hover:shadow-sm transition-all group">
            <div class="w-12 h-12 rounded-full bg-gold/30 text-brand flex items-center justify-center">
                <span class="material-symbols-outlined">confirmation_number</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">Tickets Abiertos</p>
                <p class="text-2xl font-bold leading-tight group-hover:text-brand transition-colors">{{ $ticketsAbiertos }}</p>
            </div>
        </a>

        <a href="{{ route('network.index') }}"
           class="col-span-3 bg-canvas border border-border p-6 rounded-xl flex items-center gap-4 hover:border-gold hover:shadow-sm transition-all group">
            <div class="w-12 h-12 rounded-full bg-status-critical/10 text-status-critical flex items-center justify-center">
                <span class="material-symbols-outlined">lan</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-muted">IPs en Uso</p>
                <p class="text-2xl font-bold leading-tight group-hover:text-brand transition-colors">{{ $ipsEnUso }}</p>
            </div>
        </a>
    </div>

    {{-- ── Tickets recientes (ancho completo) ──────────────────────────────── --}}
    <div class="bg-canvas border border-border rounded-xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-border flex justify-between items-center">
            <h3 class="font-bold text-lg">Tickets Recientes</h3>
            <a href="{{ route('tickets.index') }}" class="text-sm font-bold text-brand hover:underline flex items-center gap-1">
                Ver todos <span class="material-symbols-outlined text-sm">chevron_right</span>
            </a>
        </div>
        <table class="w-full text-left">
            <thead class="bg-wash border-b border-border">
                <tr>
                    <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">#</th>
                    <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Solicitante</th>
                    <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Tipo</th>
                    <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Área</th>
                    <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Estado</th>
                    <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Fecha</th>
                </tr>
            </thead>
            <tbody>
                @forelse($ticketsRecientes as $t)
                <tr class="border-b border-border hover:bg-gold/5 transition-colors">
                    <td class="px-6 py-3 font-mono text-sm text-brand">#{{ $t->id }}</td>
                    <td class="px-6 py-3 text-sm font-medium">{{ $t->nombre }}</td>
                    <td class="px-6 py-3 text-sm text-muted">{{ $t->tipo ?? '—' }}</td>
                    <td class="px-6 py-3 text-sm text-muted">{{ $t->area ?? '—' }}</td>
                    <td class="px-6 py-3">
                        @php
                            $estados = [0 => 'Abierto', 1 => 'Atendiendo', 2 => 'Cerrado'];
                            $colors  = [0 => 'var(--color-status-free)', 1 => 'var(--color-brand)', 2 => 'var(--color-status-active)'];
                        @endphp
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                              style="background:{{ $colors[$t->estado] ?? '#666' }}15; color:{{ $colors[$t->estado] ?? '#666' }}">
                            {{ $estados[$t->estado] ?? '—' }}
                        </span>
                    </td>
                    <td class="px-6 py-3 text-xs text-muted">
                        {{ $t->created_at ? \Carbon\Carbon::parse($t->created_at)->format('d/m/Y') : '—' }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-10 text-center text-muted text-sm">
                        No hay tickets registrados aún
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
</x-layouts.app>
