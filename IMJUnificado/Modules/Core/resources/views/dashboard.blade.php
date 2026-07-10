<x-layouts.app title="Dashboard — IMJUVE CRM">
<div class="p-8">

    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-[32px] font-bold leading-10 tracking-tight text-brand">Dashboard</h2>
        <p class="text-muted text-sm mt-1">Resumen operativo del sistema de TI — Instituto Mexicano de la Juventud</p>
    </div>

    {{-- KPI Bento --}}
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

    {{-- Quick access grid --}}
    <div class="grid grid-cols-12 gap-4">
        {{-- Recent tickets --}}
        <div class="col-span-8 bg-canvas border border-border rounded-xl overflow-hidden shadow-sm">
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
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-muted">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ticketsRecientes as $t)
                    <tr class="border-b border-border hover:bg-gold/5 transition-colors">
                        <td class="px-6 py-3 font-mono text-sm text-brand">#{{ $t->id }}</td>
                        <td class="px-6 py-3 text-sm font-medium">{{ $t->nombre }}</td>
                        <td class="px-6 py-3 text-sm text-muted">{{ $t->tipo ?? '—' }}</td>
                        <td class="px-6 py-3">
                            @php $estados = [0=>'Abierto',1=>'Atendiendo',2=>'Cerrado']; $colors=[0=>'var(--color-status-free)',1=>'var(--color-brand)',2=>'var(--color-status-active)']; @endphp
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                  style="background:{{ $colors[$t->estado] ?? '#666' }}15; color:{{ $colors[$t->estado] ?? '#666' }}">
                                {{ $estados[$t->estado] ?? '—' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-muted text-sm">
                            No hay tickets registrados aún
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Módulos acceso rápido --}}
        <div class="col-span-4 space-y-4">
            <div class="bg-canvas border border-border rounded-xl p-5 shadow-sm">
                <h3 class="font-bold text-sm mb-4 text-muted uppercase tracking-wider">Acceso Rápido</h3>
                <div class="space-y-2">
                    @php $modules = [
                        ['route'=>'crm.index',     'icon'=>'group',          'label'=>'Usuarios'],
                        ['route'=>'kardex.index',  'icon'=>'inventory_2',    'label'=>'Kardex'],
                        ['route'=>'network.index', 'icon'=>'lan',            'label'=>'Red e IPs'],
                        ['route'=>'tickets.index', 'icon'=>'description',    'label'=>'Tickets'],
                    ]; @endphp
                    @foreach($modules as $m)
                    <a href="{{ route($m['route']) }}"
                       class="flex items-center gap-3 px-3 py-3 rounded-lg hover:bg-gold/10 transition-colors group">
                        <div class="w-8 h-8 rounded-full bg-brand/5 text-brand flex items-center justify-center group-hover:bg-brand group-hover:text-white transition-all">
                            <span class="material-symbols-outlined text-sm">{{ $m['icon'] }}</span>
                        </div>
                        <span class="text-sm font-medium text-ink">{{ $m['label'] }}</span>
                        <span class="material-symbols-outlined text-sm text-muted ml-auto">chevron_right</span>
                    </a>
                    @endforeach
                </div>
            </div>

            <div class="bg-brand rounded-xl p-5 text-white shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-gold">admin_panel_settings</span>
                    <h3 class="font-bold text-sm">Sistema IMJUVE TI</h3>
                </div>
                <p class="text-gold text-xs mb-4 leading-relaxed">
                    Sistema unificado de gestión de activos, tickets y red institucional.
                </p>
                <p class="text-[10px] text-gold/70 uppercase tracking-wider">
                    v2.0 — {{ now()->format('Y') }}
                </p>
            </div>
        </div>
    </div>
</div>
</x-layouts.app>
