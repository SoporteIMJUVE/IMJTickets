<x-layouts.app title="Dashboard — IMJUVE CRM">
<div class="p-8">

    {{-- Header --}}
    <div class="mb-8">
        <h2 class="text-[32px] font-bold leading-10 tracking-tight text-[#621132]">Dashboard</h2>
        <p class="text-[#544246] text-sm mt-1">Resumen operativo del sistema de TI — Instituto Mexicano de la Juventud</p>
    </div>

    {{-- KPI Bento --}}
    <div class="grid grid-cols-12 gap-4 mb-8">
        <a href="{{ route('crm.index') }}"
           class="col-span-3 bg-white border border-[#E5E7EB] p-6 rounded-xl flex items-center gap-4 hover:border-[#D4C19C] hover:shadow-sm transition-all group">
            <div class="w-12 h-12 rounded-full bg-[#166534]/10 text-[#166534] flex items-center justify-center">
                <span class="material-symbols-outlined">group</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246]">Usuarios Activos</p>
                <p class="text-2xl font-bold leading-tight group-hover:text-[#621132] transition-colors">{{ $totalEmpleados }}</p>
            </div>
        </a>

        <a href="{{ route('kardex.index') }}"
           class="col-span-3 bg-white border border-[#E5E7EB] p-6 rounded-xl flex items-center gap-4 hover:border-[#D4C19C] hover:shadow-sm transition-all group">
            <div class="w-12 h-12 rounded-full bg-[#1E40AF]/10 text-[#1E40AF] flex items-center justify-center">
                <span class="material-symbols-outlined">computer</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246]">Activos Registrados</p>
                <p class="text-2xl font-bold leading-tight group-hover:text-[#621132] transition-colors">{{ $totalActivos }}</p>
            </div>
        </a>

        <a href="{{ route('tickets.index') }}"
           class="col-span-3 bg-white border border-[#E5E7EB] p-6 rounded-xl flex items-center gap-4 hover:border-[#D4C19C] hover:shadow-sm transition-all group">
            <div class="w-12 h-12 rounded-full bg-[#D4C19C]/30 text-[#621132] flex items-center justify-center">
                <span class="material-symbols-outlined">confirmation_number</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246]">Tickets Abiertos</p>
                <p class="text-2xl font-bold leading-tight group-hover:text-[#621132] transition-colors">{{ $ticketsAbiertos }}</p>
            </div>
        </a>

        <a href="{{ route('network.index') }}"
           class="col-span-3 bg-white border border-[#E5E7EB] p-6 rounded-xl flex items-center gap-4 hover:border-[#D4C19C] hover:shadow-sm transition-all group">
            <div class="w-12 h-12 rounded-full bg-[#991B1B]/10 text-[#991B1B] flex items-center justify-center">
                <span class="material-symbols-outlined">lan</span>
            </div>
            <div>
                <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246]">IPs en Uso</p>
                <p class="text-2xl font-bold leading-tight group-hover:text-[#621132] transition-colors">{{ $ipsEnUso }}</p>
            </div>
        </a>
    </div>

    {{-- Quick access grid --}}
    <div class="grid grid-cols-12 gap-4">
        {{-- Recent tickets --}}
        <div class="col-span-8 bg-white border border-[#E5E7EB] rounded-xl overflow-hidden shadow-sm">
            <div class="px-6 py-4 border-b border-[#E5E7EB] flex justify-between items-center">
                <h3 class="font-bold text-lg">Tickets Recientes</h3>
                <a href="{{ route('tickets.index') }}" class="text-sm font-bold text-[#621132] hover:underline flex items-center gap-1">
                    Ver todos <span class="material-symbols-outlined text-sm">chevron_right</span>
                </a>
            </div>
            <table class="w-full text-left">
                <thead class="bg-[#F3F4F6] border-b border-[#E5E7EB]">
                    <tr>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-[#544246]">#</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Solicitante</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Tipo</th>
                        <th class="px-6 py-3 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ticketsRecientes as $t)
                    <tr class="border-b border-[#E5E7EB] hover:bg-[#D4C19C]/5 transition-colors">
                        <td class="px-6 py-3 font-mono text-sm text-[#621132]">#{{ $t->id }}</td>
                        <td class="px-6 py-3 text-sm font-medium">{{ $t->nombre }}</td>
                        <td class="px-6 py-3 text-sm text-[#544246]">{{ $t->tipo ?? '—' }}</td>
                        <td class="px-6 py-3">
                            @php $estados = [0=>'Abierto',1=>'Atendiendo',2=>'Cerrado']; $colors=[0=>'#1E40AF',1=>'#621132',2=>'#166534']; @endphp
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold uppercase"
                                  style="background:{{ $colors[$t->estado] ?? '#666' }}15; color:{{ $colors[$t->estado] ?? '#666' }}">
                                {{ $estados[$t->estado] ?? '—' }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-[#544246] text-sm">
                            No hay tickets registrados aún
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Módulos acceso rápido --}}
        <div class="col-span-4 space-y-4">
            <div class="bg-white border border-[#E5E7EB] rounded-xl p-5 shadow-sm">
                <h3 class="font-bold text-sm mb-4 text-[#544246] uppercase tracking-wider">Acceso Rápido</h3>
                <div class="space-y-2">
                    @php $modules = [
                        ['route'=>'crm.index',     'icon'=>'group',          'label'=>'Usuarios'],
                        ['route'=>'kardex.index',  'icon'=>'inventory_2',    'label'=>'Kardex'],
                        ['route'=>'network.index', 'icon'=>'lan',            'label'=>'Red e IPs'],
                        ['route'=>'tickets.index', 'icon'=>'description',    'label'=>'Tickets'],
                    ]; @endphp
                    @foreach($modules as $m)
                    <a href="{{ route($m['route']) }}"
                       class="flex items-center gap-3 px-3 py-3 rounded-lg hover:bg-[#D4C19C]/10 transition-colors group">
                        <div class="w-8 h-8 rounded-full bg-[#621132]/5 text-[#621132] flex items-center justify-center group-hover:bg-[#621132] group-hover:text-white transition-all">
                            <span class="material-symbols-outlined text-sm">{{ $m['icon'] }}</span>
                        </div>
                        <span class="text-sm font-medium text-[#1b1c1c]">{{ $m['label'] }}</span>
                        <span class="material-symbols-outlined text-sm text-[#544246] ml-auto">chevron_right</span>
                    </a>
                    @endforeach
                </div>
            </div>

            <div class="bg-[#621132] rounded-xl p-5 text-white shadow-sm">
                <div class="flex items-center gap-2 mb-2">
                    <span class="material-symbols-outlined text-[#D4C19C]">admin_panel_settings</span>
                    <h3 class="font-bold text-sm">Sistema IMJUVE TI</h3>
                </div>
                <p class="text-[#D4C19C] text-xs mb-4 leading-relaxed">
                    Sistema unificado de gestión de activos, tickets y red institucional.
                </p>
                <p class="text-[10px] text-[#D4C19C]/70 uppercase tracking-wider">
                    v2.0 — {{ now()->format('Y') }}
                </p>
            </div>
        </div>
    </div>
</div>
</x-layouts.app>
