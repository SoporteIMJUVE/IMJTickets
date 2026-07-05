<x-layouts.app title="Tickets — IMJUVE CRM">
<div class="p-8" id="tickets-page">

    {{-- Banner auto-refresh --}}
    <div id="banner-nuevos" class="hidden mb-4 flex items-center justify-between bg-[#DBEAFE] border border-[#1E40AF]/30 rounded-xl px-5 py-3">
        <div class="flex items-center gap-3 text-[#1E40AF]">
            <span class="material-symbols-outlined">notification_important</span>
            <span class="text-sm font-bold" id="banner-count">Nuevos tickets</span>
        </div>
        <button onclick="location.reload()" class="text-xs font-bold bg-[#1E40AF] text-white px-4 py-1.5 rounded-lg hover:opacity-90">
            Actualizar
        </button>
    </div>

    {{-- Header + toggle de vista --}}
    <div class="flex justify-between items-end mb-6">
        <div>
            <h2 class="text-[32px] font-bold leading-10 tracking-tight text-[#621132]">Gestión de Tickets</h2>
            <p class="text-[#544246] text-sm mt-1">Soporte técnico y seguimiento de incidencias.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="flex bg-[#F3F4F6] rounded-lg p-1">
                <button onclick="setView('list')" id="btn-list"
                        class="px-4 py-2 rounded-md text-sm font-bold transition-all text-[#544246] hover:bg-[#eae8e7]">
                    <span class="material-symbols-outlined text-sm align-middle">format_list_bulleted</span>
                    Lista
                </button>
                <button onclick="setView('kanban')" id="btn-kanban"
                        class="px-4 py-2 rounded-md text-sm font-bold transition-all bg-white text-[#621132] shadow-sm">
                    <span class="material-symbols-outlined text-sm align-middle">view_kanban</span>
                    Kanban
                </button>
            </div>
            <a href="{{ route('tickets.create') }}" class="px-4 py-2 bg-[#621132] text-white rounded-lg flex items-center gap-2 hover:opacity-90 text-sm font-bold">
                <span class="material-symbols-outlined text-sm">add</span>
                Nuevo Ticket
            </a>
        </div>
    </div>

    {{-- Barra de filtros --}}
    <div class="flex items-center gap-3 mb-6 flex-wrap">
        <div class="flex items-center gap-2 bg-white border border-[#E5E7EB] px-3 py-1.5 rounded-lg hover:border-[#D4C19C] transition-colors">
            <span class="material-symbols-outlined text-sm text-[#621132]">filter_alt</span>
            <span class="text-xs font-bold uppercase tracking-wide text-[#544246]">Área:</span>
            <select id="filtro-area" onchange="aplicarFiltros()" class="bg-transparent border-none text-xs font-medium p-0 focus:ring-0 outline-none">
                <option value="">Todas</option>
                @foreach($areas as $a)
                <option value="{{ $a }}">{{ $a }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-2 bg-white border border-[#E5E7EB] px-3 py-1.5 rounded-lg hover:border-[#D4C19C] transition-colors">
            <span class="material-symbols-outlined text-sm text-[#621132]">stars</span>
            <span class="text-xs font-bold uppercase tracking-wide text-[#544246]">Estado:</span>
            <select id="filtro-estado" onchange="aplicarFiltros()" class="bg-transparent border-none text-xs font-medium p-0 focus:ring-0 outline-none">
                <option value="">Todos</option>
                <option value="0">Abierto</option>
                <option value="1">Atendiendo</option>
                <option value="2">Cerrado</option>
            </select>
        </div>
        <div class="flex items-center gap-2 bg-white border border-[#E5E7EB] px-3 py-1.5 rounded-lg hover:border-[#D4C19C] transition-colors">
            <span class="material-symbols-outlined text-sm text-[#621132]">calendar_today</span>
            <span class="text-xs font-bold uppercase tracking-wide text-[#544246]">Fecha:</span>
            <select id="filtro-fecha" onchange="aplicarFiltros()" class="bg-transparent border-none text-xs font-medium p-0 focus:ring-0 outline-none">
                <option value="">Todo</option>
                <option value="1">Hoy</option>
                <option value="7">Últimos 7 días</option>
                <option value="30">Últimos 30 días</option>
            </select>
        </div>
        <div class="ml-auto text-xs text-[#544246]">
            Total: <span class="font-bold text-[#621132]" id="total-visible">{{ $tickets->count() }}</span> tickets
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         VISTA LISTA
    ═══════════════════════════════════════════════ --}}
    <div id="view-list" class="hidden">
        <div class="bg-white border border-[#E5E7EB] rounded-xl overflow-hidden shadow-sm">
            <table class="w-full text-left">
                <thead class="bg-[#F3F4F6] border-b border-[#E5E7EB]">
                    <tr>
                        <th class="px-5 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">ID Folio</th>
                        <th class="px-5 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Solicitante</th>
                        <th class="px-5 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Área / Tipo</th>
                        <th class="px-5 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Descripción</th>
                        <th class="px-5 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Estado</th>
                        <th class="px-5 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">Creado</th>
                        <th class="px-5 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246] text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody id="list-tbody" class="divide-y divide-[#E5E7EB]">
                    @forelse($tickets as $t)
                    @php
                        $folio = '#TK-' . \Carbon\Carbon::parse($t->created_at)->format('Y') . '-' . str_pad($t->id, 4, '0', STR_PAD_LEFT);
                        $estadoConf = match($t->estado) {
                            0 => ['label'=>'Abierto',    'style'=>'background:#DBEAFE;color:#1E40AF'],
                            1 => ['label'=>'Atendiendo', 'style'=>'background:#FEF3C7;color:#92400E'],
                            2 => ['label'=>'Cerrado',    'style'=>'background:#F3F4F6;color:#544246'],
                            default => ['label'=>'—','style'=>''],
                        };
                        $td = ['id'=>$t->id,'folio'=>$folio,'nombre'=>$t->nombre,'correo'=>$t->correo,'area'=>$t->area,'tipo'=>$t->tipo,'descripcion'=>$t->descripcion,'estado'=>$t->estado,'atendido_by'=>$t->atendido_by,'created_at'=>$t->created_at,'atendido_at'=>$t->atendido_at,'cerrado_at'=>$t->cerrado_at,'ip'=>$t->ip??null,'mac'=>$t->mac??null];
                    @endphp
                    <tr class="hover:bg-[#D4C19C]/5 transition-colors group cursor-pointer list-row"
                        data-ticket-id="{{ $t->id }}"
                        data-area="{{ $t->area }}"
                        data-estado="{{ $t->estado }}"
                        data-fecha="{{ $t->created_at }}"
                        data-ticket="{{ json_encode($td) }}"
                        onclick="openPanel(this)">
                        <td class="px-5 py-4 font-mono text-xs text-[#621132] font-bold">{{ $folio }}</td>
                        <td class="px-5 py-4">
                            <p class="font-bold text-sm text-[#1b1c1c]">{{ $t->nombre }}</p>
                            <p class="text-[11px] text-[#544246]">{{ $t->correo }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <p class="text-xs font-bold text-[#621132]">{{ $t->area }}</p>
                            <p class="text-[10px] text-[#544246] mt-0.5 uppercase tracking-wide">{{ $t->tipo }}</p>
                        </td>
                        <td class="px-5 py-4 max-w-xs">
                            <p class="text-xs text-[#544246] line-clamp-2">{{ $t->descripcion }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <span class="row-estado-badge px-2 py-0.5 rounded-full text-[10px] font-bold uppercase" style="{{ $estadoConf['style'] }}">
                                {{ $estadoConf['label'] }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-xs text-[#544246]">
                            {{ \Carbon\Carbon::parse($t->created_at)->format('d M, H:i') }}
                        </td>
                        <td class="px-5 py-4 text-right">
                            <div class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button class="p-1.5 hover:bg-[#D4C19C]/20 rounded text-[#621132]" title="Responder" onclick="event.stopPropagation(); openPanel(this.closest('tr'))">
                                    <span class="material-symbols-outlined text-lg">reply</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center text-[#544246]">
                            <span class="material-symbols-outlined text-4xl block mb-2">confirmation_number</span>
                            <p class="text-sm">No hay tickets registrados</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════
         VISTA KANBAN
    ═══════════════════════════════════════════════ --}}
    <div id="view-kanban">
        @php
            $columns = [
                ['estado'=>0,'label'=>'ABIERTO',    'color'=>'#1E40AF','border'=>'border-[#1E40AF]','icon'=>'inbox'],
                ['estado'=>1,'label'=>'ATENDIENDO', 'color'=>'#9A3412','border'=>'border-[#9A3412]','icon'=>'pending_actions'],
                ['estado'=>2,'label'=>'CERRADO',    'color'=>'#991B1B','border'=>'border-[#991B1B]','icon'=>'task_alt'],
            ];
        @endphp
        <div class="grid grid-cols-3 gap-6">
            @foreach($columns as $col)
            @php $colTickets = $porEstado[$col['estado']]; @endphp
            <div>
                {{-- Cabecera de columna --}}
                <div class="flex items-center justify-between pb-3 mb-4 border-b-2 {{ $col['border'] }}">
                    <h3 class="font-bold text-sm flex items-center gap-2" style="color:{{ $col['color'] }}">
                        <span class="w-2 h-2 rounded-full inline-block" style="background:{{ $col['color'] }}"></span>
                        {{ $col['label'] }}
                    </h3>
                    <div class="flex items-center gap-2">
                        @if($col['estado'] === 2)
                        <span id="cerrados-ocultos" class="hidden text-[10px] text-[#544246] italic"></span>
                        @endif
                        <span id="badge-estado-{{ $col['estado'] }}" class="text-xs font-bold text-white px-2 py-0.5 rounded-full"
                              style="background:{{ $col['color'] }}">
                            {{ $colTickets->count() }}
                        </span>
                    </div>
                </div>

                {{-- Cuerpo de columna (drag & drop target) --}}
                @php $bgCerrado = $col['estado'] === 2 ? 'bg-[#F3F4F6]/60 rounded-lg p-2' : ''; @endphp
                <div class="kanban-col-body space-y-3 min-h-[200px] {{ $bgCerrado }}"
                     data-estado="{{ $col['estado'] }}">
                    @forelse($colTickets as $t)
                    @php
                        $folio = '#TK-' . \Carbon\Carbon::parse($t->created_at)->format('Y') . '-' . str_pad($t->id, 4, '0', STR_PAD_LEFT);
                        $td = ['id'=>$t->id,'folio'=>$folio,'nombre'=>$t->nombre,'correo'=>$t->correo,'area'=>$t->area,'tipo'=>$t->tipo,'descripcion'=>$t->descripcion,'estado'=>$t->estado,'atendido_by'=>$t->atendido_by,'created_at'=>$t->created_at,'atendido_at'=>$t->atendido_at,'cerrado_at'=>$t->cerrado_at,'ip'=>$t->ip??null,'mac'=>$t->mac??null];
                        $isCerrado = $col['estado'] === 2;
                    @endphp
                    <div class="ticket-card bg-white rounded-xl border border-[#E5E7EB] shadow-sm overflow-hidden hover:border-[#D4C19C] transition-all {{ $isCerrado ? 'opacity-75 grayscale' : '' }}"
                         data-ticket="{{ json_encode($td) }}"
                         data-ticket-id="{{ $t->id }}"
                         data-area="{{ $t->area }}"
                         data-estado="{{ $t->estado }}"
                         data-fecha="{{ $t->created_at }}"
                         data-cerrado-at="{{ $t->cerrado_at ?? '' }}">
                        {{-- Handle de arrastre (drag aquí) --}}
                        <div class="drag-handle h-7 flex items-center justify-between px-3 cursor-grab active:cursor-grabbing select-none"
                             style="background:{{ $col['color'] }}15; border-bottom: 1px solid {{ $col['color'] }}20">
                            <span class="material-symbols-outlined text-[14px] opacity-40" style="color:{{ $col['color'] }}">drag_indicator</span>
                            <span class="font-mono text-[10px] text-[#544246]">{{ $folio }}</span>
                        </div>
                        {{-- Cuerpo clickeable → abre panel --}}
                        <div class="p-4 cursor-pointer" onclick="openPanel(this.closest('.ticket-card'))">
                            <div class="flex items-start gap-2 mb-2">
                                <span class="material-symbols-outlined text-sm opacity-40 mt-0.5" style="color:{{ $col['color'] }}" id="icono-tipo-{{ $t->id }}">confirmation_number</span>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-[#1b1c1c] truncate">{{ $t->nombre }}</p>
                                    <p class="text-[10px] text-[#544246] uppercase tracking-wide">{{ $t->tipo }}</p>
                                </div>
                            </div>
                            <p class="text-xs text-[#544246] line-clamp-2 mb-3">{{ $t->descripcion }}</p>
                            <div class="flex items-center justify-between pt-3 border-t border-[#E5E7EB]">
                                <div class="flex items-center gap-1 text-[10px] font-bold tiempo-label" style="color:{{ $col['color'] }}"
                                     data-ts="{{ $t->created_at }}"
                                     data-estado="{{ $t->estado }}">
                                    <span class="material-symbols-outlined text-[13px]">{{ $col['estado'] === 2 ? 'check_circle' : 'schedule' }}</span>
                                    <span>—</span>
                                </div>
                                <div class="w-6 h-6 rounded-full bg-[#D4C19C] flex items-center justify-center text-[9px] font-bold text-[#621132]">
                                    {{ strtoupper(substr($t->nombre ?? 'U', 0, 1)) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @empty
                    <div class="border-2 border-dashed border-[#E5E7EB] rounded-xl p-6 text-center text-[#544246] placeholder-card">
                        <span class="material-symbols-outlined text-2xl block mb-1">{{ $col['icon'] }}</span>
                        <p class="text-xs">Sin tickets</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

{{-- ══════════════════════════════════════════════
     PANEL LATERAL — estructura del wireframe
═══════════════════════════════════════════════ --}}
<aside class="fixed top-0 right-0 h-screen w-[420px] bg-white shadow-2xl border-l border-[#D4C19C] z-[60] detail-panel closed flex flex-col"
       id="ticket-panel">

    {{-- Header fijo: título + cerrar --}}
    <div class="h-16 flex items-center justify-between px-6 bg-[#fbf9f8] border-b border-[#E5E7EB] shrink-0">
        <h3 class="font-bold text-[#621132] text-sm tracking-wide">Detalles del Ticket</h3>
        <button class="p-2 hover:bg-[#F3F4F6] rounded-lg transition-colors" onclick="closePanel()">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    {{-- Body scrollable --}}
    <div class="flex-1 overflow-y-auto custom-scrollbar p-6 space-y-5">

        {{-- Folio + estado --}}
        <div class="flex items-center justify-between">
            <span class="font-mono text-sm bg-[#F3F4F6] px-3 py-1 rounded text-[#621132] font-bold" id="panel-folio">#TK-—</span>
            <span class="px-3 py-1 rounded-full text-[11px] font-bold uppercase" id="panel-estado-badge">—</span>
        </div>

        {{-- Tipo + tiempo --}}
        <div class="flex items-center gap-2 text-xs text-[#544246] -mt-2">
            <span id="panel-tipo-label">—</span>
            <span class="opacity-30">·</span>
            <span class="material-symbols-outlined text-[13px]" id="panel-tiempo-icon">schedule</span>
            <span id="panel-tiempo-val">—</span>
        </div>

        {{-- Información del Solicitante --}}
        <div>
            <label class="text-[10px] font-bold uppercase tracking-widest text-[#544246] block mb-2">Información del Solicitante</label>
            <div class="flex items-center gap-4 p-4 bg-[#F3F4F6] rounded-xl border border-[#E5E7EB]">
                <div class="w-12 h-12 bg-[#D4C19C]/40 rounded-full flex items-center justify-center text-[#621132] font-bold text-lg flex-shrink-0"
                     id="panel-avatar">?</div>
                <div class="min-w-0">
                    <p class="font-bold text-[#1b1c1c]" id="panel-nombre">—</p>
                    <p class="text-xs text-[#544246]" id="panel-correo">—</p>
                    <p class="text-[10px] font-bold text-[#621132] uppercase mt-1" id="panel-area">—</p>
                </div>
            </div>
        </div>

        {{-- Descripción del Incidente --}}
        <div>
            <label class="text-[10px] font-bold uppercase tracking-widest text-[#544246] block mb-2">Descripción del Incidente</label>
            <div class="p-4 bg-white border border-[#E5E7EB] rounded-xl text-sm leading-relaxed text-[#1b1c1c]"
                 id="panel-desc">—</div>
        </div>

        {{-- Datos de Red (solo TI) --}}
        <div>
            <label class="text-[10px] font-bold uppercase tracking-widest text-[#544246] block mb-2">Datos de Red <span class="normal-case font-normal">(solo TI)</span></label>
            <div class="bg-[#621132] text-white px-4 py-3 rounded-xl space-y-2">
                <div class="flex justify-between items-center">
                    <span class="text-[10px] font-bold opacity-60 uppercase">IP</span>
                    <span class="font-mono text-xs" id="panel-ip">—</span>
                </div>
                <div class="h-px bg-white/20"></div>
                <div class="flex justify-between items-center">
                    <span class="text-[10px] font-bold opacity-60 uppercase">MAC</span>
                    <span class="font-mono text-xs" id="panel-mac">—</span>
                </div>
            </div>
        </div>

        {{-- Historial / Comentarios --}}
        <div>
            <label class="text-[10px] font-bold uppercase tracking-widest text-[#544246] block mb-3">Historial / Comentarios</label>
            <div id="panel-comentarios" class="space-y-4">
                <p class="text-xs text-[#544246] text-center py-4">Sin comentarios aún</p>
            </div>
        </div>

    </div>

    {{-- Footer: acciones (wireframe) --}}
    <div class="p-5 border-t border-[#E5E7EB] bg-[#fbf9f8] shrink-0 space-y-3">

        {{-- Asignar técnico --}}
        <select id="panel-asignar"
                class="w-full bg-[#F3F4F6] border border-[#E5E7EB] rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#621132] outline-none">
            <option value="">Sin asignar técnico</option>
            @foreach($tecnicos as $tec)
            <option value="{{ $tec->email }}">{{ $tec->name }}</option>
            @endforeach
        </select>

        {{-- Textarea respuesta --}}
        <textarea id="textarea-comentar" rows="2"
                  class="w-full bg-[#F3F4F6] border border-[#E5E7EB] rounded-lg p-3 text-sm resize-none focus:ring-2 focus:ring-[#621132] outline-none"
                  placeholder="Escribe tu respuesta al ticket..."></textarea>

        {{-- Responder Ticket (CTA principal) --}}
        <button id="btn-responder"
                class="w-full bg-[#621132] text-white py-3 rounded-xl font-bold text-sm hover:opacity-90 transition-all flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-lg">reply</span>
            Responder Ticket
        </button>

        {{-- Cambiar estado --}}
        <div class="grid grid-cols-3 gap-2">
            <button class="estado-btn py-2.5 text-xs font-bold border-2 rounded-xl transition-all"
                    data-estado="0" style="border-color:#1E40AF;color:#1E40AF">
                Abierto
            </button>
            <button class="estado-btn py-2.5 text-xs font-bold border-2 rounded-xl transition-all"
                    data-estado="1" style="border-color:#9A3412;color:#9A3412">
                Atendiendo
            </button>
            <button class="estado-btn py-2.5 text-xs font-bold rounded-xl bg-[#991B1B] text-white hover:opacity-90 transition-all border-2 border-[#991B1B]"
                    data-estado="2">
                Cerrar Caso
            </button>
        </div>
    </div>
</aside>

<div class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[55] hidden" id="panel-backdrop" onclick="closePanel()"></div>

<script>
// ─── Datos globales ───────────────────────────────────────────────────────────
const CSRF        = '{{ csrf_token() }}';
const comentarios = {!! json_encode($comentariosPorTicket, JSON_HEX_TAG) !!};
let currentTicketId = null;
let lastAbiertos    = {{ $porEstado[0]->count() }};
let _isDragging     = false;

// ─── Utilidades ───────────────────────────────────────────────────────────────
function tiempoTranscurrido(ts) {
    if (!ts) return '';
    const diff = Date.now() - new Date(ts).getTime();
    const min  = Math.floor(diff / 60000);
    if (min < 1)  return 'Ahora';
    if (min < 60) return min + 'm';
    const h = Math.floor(min / 60);
    if (h < 24)   return h + 'h ' + (min % 60) + 'm';
    return Math.floor(h / 24) + 'd ' + (h % 24) + 'h';
}

function tipoIcono(tipo) {
    const t = (tipo || '').toLowerCase();
    if (/red|vpn|internet|ip|lan|cable|wifi/.test(t))              return 'lan';
    if (/hardware|equipo|laptop|pc|monitor|teclado|mouse/.test(t)) return 'computer';
    if (/software|programa|sistema|aplicaci/.test(t))              return 'code';
    if (/manten|repara|servicio|preventivo/.test(t))               return 'build';
    if (/impres|escaner|cartucho|toner/.test(t))                   return 'print';
    if (/correo|email|cuenta|usuario|acceso|contrase/.test(t))     return 'person';
    return 'confirmation_number';
}

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ─── Toggle de vista ──────────────────────────────────────────────────────────
function setView(mode) {
    const isKanban = mode === 'kanban';
    document.getElementById('view-kanban').classList.toggle('hidden', !isKanban);
    document.getElementById('view-list').classList.toggle('hidden', isKanban);
    const active   = 'px-4 py-2 rounded-md text-sm font-bold transition-all bg-white text-[#621132] shadow-sm';
    const inactive = 'px-4 py-2 rounded-md text-sm font-bold transition-all text-[#544246] hover:bg-[#eae8e7]';
    document.getElementById('btn-kanban').className = isKanban ? active : inactive;
    document.getElementById('btn-list').className   = !isKanban ? active : inactive;
}

// ─── Tiempos en kanban ────────────────────────────────────────────────────────
function actualizarTiempos() {
    document.querySelectorAll('.tiempo-label').forEach(el => {
        const span = el.querySelector('span:last-child');
        if (!span) return;
        if (parseInt(el.dataset.estado) === 2) { span.textContent = 'Finalizado'; return; }
        span.textContent = tiempoTranscurrido(el.dataset.ts);
    });
}

function renderizarIconosTipo() {
    document.querySelectorAll('[id^="icono-tipo-"]').forEach(el => {
        try {
            const td = JSON.parse(el.closest('.ticket-card')?.getAttribute('data-ticket') ?? 'null');
            if (td) el.textContent = tipoIcono(td.tipo);
        } catch(e) {}
    });
}

// ─── Filtros ──────────────────────────────────────────────────────────────────
function aplicarFiltros() {
    const area   = document.getElementById('filtro-area').value;
    const estado = document.getElementById('filtro-estado').value;
    const dias   = parseInt(document.getElementById('filtro-fecha').value) || 0;
    const ahora  = Date.now();

    document.querySelectorAll('#list-tbody .list-row').forEach(row => {
        const ok = (!area   || row.dataset.area   === area)
                && (!estado || row.dataset.estado === estado)
                && (!dias   || (ahora - new Date(row.dataset.fecha).getTime()) < dias * 864e5);
        row.style.display = ok ? '' : 'none';
    });
    document.querySelectorAll('.kanban-col-body .ticket-card').forEach(card => {
        const ok = (!area   || card.dataset.area   === area)
                && (!estado || card.dataset.estado === estado)
                && (!dias   || (ahora - new Date(card.dataset.fecha).getTime()) < dias * 864e5);
        card.classList.toggle('hidden', !ok);
    });
    actualizarContadoresColumnas();
}

// ─── Auto-ocultar cerrados > 1h ───────────────────────────────────────────────
function aplicarAutoHideCerrados() {
    let ocultos = 0;
    document.querySelectorAll('.kanban-col-body[data-estado="2"] .ticket-card').forEach(card => {
        const ca = card.dataset.cerradoAt;
        if (ca && (Date.now() - new Date(ca).getTime()) > 3600000) {
            card.style.display = 'none';
            card.classList.add('auto-hidden');
            ocultos++;
        }
    });
    const label = document.getElementById('cerrados-ocultos');
    if (label) {
        label.textContent = ocultos > 0 ? ocultos + ' oculto' + (ocultos > 1 ? 's' : '') + ' (>1h)' : '';
        label.classList.toggle('hidden', ocultos === 0);
    }
    actualizarContadoresColumnas();
}

// ─── Kanban contadores ────────────────────────────────────────────────────────
function actualizarContadoresColumnas() {
    [0, 1, 2].forEach(e => {
        const col   = document.querySelector(`.kanban-col-body[data-estado="${e}"]`);
        const badge = document.getElementById(`badge-estado-${e}`);
        if (col && badge) badge.textContent = col.querySelectorAll('.ticket-card:not(.hidden):not(.auto-hidden)').length;
    });
}

// ─── Drag & Drop ──────────────────────────────────────────────────────────────
const HANDLE_COLORS = { 0: '#1E40AF', 1: '#9A3412', 2: '#991B1B' };

function inicializarSortable() {
    if (typeof window.Sortable === 'undefined') { setTimeout(inicializarSortable, 80); return; }
    document.querySelectorAll('.kanban-col-body').forEach(col => {
        window.Sortable.create(col, {
            group: 'kanban', animation: 150, handle: '.drag-handle', ghostClass: 'opacity-20',
            onStart: () => { _isDragging = true; },
            onEnd: async (evt) => {
                setTimeout(() => { _isDragging = false; }, 150);
                if (evt.from === evt.to) return;
                const ticketId    = parseInt(evt.item.dataset.ticketId);
                const nuevoEstado = parseInt(evt.to.dataset.estado);
                if (isNaN(ticketId) || isNaN(nuevoEstado)) return;
                evt.item.dataset.estado = nuevoEstado;
                // Actualiza el JSON inline del card para que el panel refleje el nuevo estado
                try {
                    const raw = evt.item.getAttribute('data-ticket');
                    if (raw) {
                        const td = JSON.parse(raw);
                        td.estado = nuevoEstado;
                        evt.item.setAttribute('data-ticket', JSON.stringify(td));
                    }
                } catch (_) {}
                const handle = evt.item.querySelector('.drag-handle');
                if (handle) handle.style.background = (HANDLE_COLORS[nuevoEstado] ?? '#544246') + '15';
                evt.item.classList.toggle('opacity-75', nuevoEstado === 2);
                evt.item.classList.toggle('grayscale',  nuevoEstado === 2);
                await fetchJson(`/tickets/${ticketId}/estado`, 'POST', { estado: nuevoEstado });
                actualizarContadoresColumnas();
                aplicarAutoHideCerrados();
                checkNuevosTickets();
            }
        });
    });
}

// ─── Panel: abrir ─────────────────────────────────────────────────────────────
function openPanel(el) {
    if (_isDragging) return;
    try {
        const raw = el.getAttribute('data-ticket');
        if (!raw) return;
        const td = JSON.parse(raw);
        if (!td) return;
        currentTicketId = td.id;

        // Folio + estado
        const ESTADOS = {
            0: { label:'Abierto',    bg:'#DBEAFE', color:'#1E40AF' },
            1: { label:'Atendiendo', bg:'#FEF3C7', color:'#92400E' },
            2: { label:'Cerrado',    bg:'#F3F4F6', color:'#544246' },
        };
        const ec = ESTADOS[td.estado] ?? { label:'—', bg:'#F3F4F6', color:'#544246' };

        document.getElementById('panel-folio').textContent = td.folio || ('#TK-' + String(td.id).padStart(4, '0'));
        const badge = document.getElementById('panel-estado-badge');
        badge.textContent = ec.label; badge.style.background = ec.bg; badge.style.color = ec.color;

        // Tipo + tiempo
        document.getElementById('panel-tipo-label').textContent = td.tipo || '—';
        const iconEl = document.getElementById('panel-tiempo-icon');
        const valEl  = document.getElementById('panel-tiempo-val');
        if (iconEl) iconEl.textContent = td.estado === 2 ? 'check_circle' : 'schedule';
        if (valEl)  valEl.textContent  = td.estado === 2
            ? (td.cerrado_at ? 'Cerrado hace ' + tiempoTranscurrido(td.cerrado_at) : 'Cerrado')
            : (td.created_at ? 'Hace '         + tiempoTranscurrido(td.created_at) : '—');

        // Solicitante
        document.getElementById('panel-avatar').textContent = (td.nombre || 'U').charAt(0).toUpperCase();
        document.getElementById('panel-nombre').textContent = td.nombre  || '—';
        document.getElementById('panel-correo').textContent = td.correo  || '—';
        document.getElementById('panel-area').textContent   = td.area    || '—';

        // Descripción
        document.getElementById('panel-desc').textContent = td.descripcion || '—';

        // IP / MAC
        document.getElementById('panel-ip').textContent  = td.ip  || 'No disponible';
        document.getElementById('panel-mac').textContent = td.mac || 'No disponible';

        // Asignar técnico
        const sel = document.getElementById('panel-asignar');
        if (sel) sel.value = td.atendido_by || '';

        // Botones estado activo
        document.querySelectorAll('.estado-btn').forEach(btn => {
            const activo = parseInt(btn.dataset.estado) === td.estado;
            btn.style.fontWeight    = activo ? '900' : '600';
            btn.style.outline       = activo ? '2px solid currentColor' : 'none';
            btn.style.outlineOffset = '2px';
        });

        // Comentarios
        const thread = document.getElementById('panel-comentarios');
        const coms   = comentarios[td.id] || [];
        thread.innerHTML = coms.length
            ? coms.map(renderComentario).join('')
            : '<p class="text-xs text-[#544246] text-center py-4">Sin comentarios aún</p>';

        document.getElementById('textarea-comentar').value = '';
        document.getElementById('ticket-panel').classList.remove('closed');
        document.getElementById('panel-backdrop').classList.remove('hidden');
    } catch (err) {
        console.error('openPanel error:', err);
    }
}

// ─── Panel: cerrar ────────────────────────────────────────────────────────────
function closePanel() {
    document.getElementById('ticket-panel').classList.add('closed');
    document.getElementById('panel-backdrop').classList.add('hidden');
    currentTicketId = null;
}

// ─── Comentarios: render ──────────────────────────────────────────────────────
function renderComentario(c) {
    const ini  = (c.autor_nombre || 'A').charAt(0).toUpperCase();
    const fecha = c.created_at
        ? new Date(c.created_at).toLocaleString('es-MX', { day:'2-digit', month:'short', hour:'2-digit', minute:'2-digit' })
        : '';
    return `<div class="flex gap-3">
        <div class="shrink-0 w-8 h-8 rounded-full bg-[#621132] text-white flex items-center justify-center text-[10px] font-bold">${escHtml(ini)}</div>
        <div class="flex-1 p-3 bg-[#F3F4F6] rounded-xl text-xs">
            <p class="font-bold text-[#621132]">${escHtml(c.autor_nombre)} <span class="font-normal text-[#544246] ml-1">${fecha}</span></p>
            <p class="mt-1 text-[#1b1c1c]">${escHtml(c.texto)}</p>
        </div>
    </div>`;
}

// ─── Fetch helper ─────────────────────────────────────────────────────────────
async function fetchJson(url, method, body) {
    try {
        const r = await fetch(url, {
            method,
            headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN':CSRF, 'X-Requested-With':'XMLHttpRequest' },
            body: JSON.stringify(body),
        });
        if (!r.ok) { console.warn(method, url, r.status); return {}; }
        return await r.json();
    } catch (e) { console.error('fetchJson:', e); return {}; }
}

// ─── Auto-refresh ─────────────────────────────────────────────────────────────
async function checkNuevosTickets() {
    try {
        const data = await (await fetch('/tickets/api/conteo', { headers:{'X-Requested-With':'XMLHttpRequest'} })).json();
        if (data.abiertos > lastAbiertos) {
            const n = data.abiertos - lastAbiertos;
            document.getElementById('banner-count').textContent = n + ' nuevo' + (n > 1 ? 's' : '') + ' ticket' + (n > 1 ? 's' : '');
            document.getElementById('banner-nuevos').classList.remove('hidden');
            lastAbiertos = data.abiertos;
        }
    } catch(e) {}
}

// ─── Inicialización — TODO dentro de DOMContentLoaded ─────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    actualizarTiempos();
    renderizarIconosTipo();
    aplicarAutoHideCerrados();
    inicializarSortable();
    setInterval(actualizarTiempos,        60000);
    setInterval(aplicarAutoHideCerrados,  60000);
    // Sin polling — el conteo se dispara al volver a la pestaña y tras cada acción
    window.addEventListener('focus', checkNuevosTickets);

    // Responder Ticket
    document.getElementById('btn-responder')?.addEventListener('click', async () => {
        if (!currentTicketId) return;
        const ta    = document.getElementById('textarea-comentar');
        const texto = ta?.value.trim() ?? '';
        if (!texto) return;
        const data = await fetchJson(`/tickets/${currentTicketId}/comentar`, 'POST', { texto });
        if (data.ok && ta) {
            ta.value = '';
            const thread = document.getElementById('panel-comentarios');
            if (thread.querySelector('p.text-center')) thread.innerHTML = '';
            thread.insertAdjacentHTML('beforeend', renderComentario({
                autor_nombre: data.comentario?.autor_nombre ?? 'TI',
                texto:        data.comentario?.texto        ?? texto,
                created_at:   data.comentario?.created_at  ?? new Date().toISOString(),
            }));
            if (!comentarios[currentTicketId]) comentarios[currentTicketId] = [];
            comentarios[currentTicketId].push(data.comentario);
        }
    });

    // Cambiar estado
    document.querySelectorAll('.estado-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!currentTicketId) return;
            const nuevoEstado = parseInt(btn.dataset.estado);
            const result = await fetchJson(`/tickets/${currentTicketId}/estado`, 'POST', { estado: nuevoEstado });
            if (!result.ok) return;

            const ESTADOS = {
                0:{ label:'Abierto', bg:'#DBEAFE', color:'#1E40AF' },
                1:{ label:'Atendiendo', bg:'#FEF3C7', color:'#92400E' },
                2:{ label:'Cerrado', bg:'#F3F4F6', color:'#544246' },
            };
            const ec = ESTADOS[nuevoEstado] ?? { label:'—', bg:'#F3F4F6', color:'#544246' };
            const badge = document.getElementById('panel-estado-badge');
            if (badge) { badge.textContent = ec.label; badge.style.background = ec.bg; badge.style.color = ec.color; }

            document.querySelectorAll('.estado-btn').forEach(b => {
                const activo = parseInt(b.dataset.estado) === nuevoEstado;
                b.style.fontWeight = activo ? '900' : '600';
                b.style.outline    = activo ? '2px solid currentColor' : 'none';
            });

            // Actualiza card Kanban
            const card = document.querySelector(`.ticket-card[data-ticket-id="${currentTicketId}"]`);
            if (card) {
                const col = document.querySelector(`.kanban-col-body[data-estado="${nuevoEstado}"]`);
                if (col) { col.prepend(card); card.dataset.estado = nuevoEstado; }
                card.classList.toggle('opacity-75', nuevoEstado === 2);
                card.classList.toggle('grayscale',  nuevoEstado === 2);
            }
            // Actualiza badge en la fila de la tabla
            const row = document.querySelector(`tr[data-ticket-id="${currentTicketId}"]`);
            if (row) {
                row.dataset.estado = nuevoEstado;
                const rowBadge = row.querySelector('.row-estado-badge');
                if (rowBadge) {
                    rowBadge.textContent = ec.label;
                    rowBadge.style.cssText = `background:${ec.bg};color:${ec.color}`;
                }
            }
            actualizarContadoresColumnas();
            if (nuevoEstado === 2) aplicarAutoHideCerrados();
            checkNuevosTickets();
        });
    });

    // Asignar técnico
    document.getElementById('panel-asignar')?.addEventListener('change', async (e) => {
        if (!currentTicketId || !e.target.value) return;
        await fetchJson(`/tickets/${currentTicketId}/asignar`, 'PATCH', { tecnico_email: e.target.value });
    });

    // Escape cierra panel
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closePanel(); });
});
</script>
</x-layouts.app>
