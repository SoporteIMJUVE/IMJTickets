<div class="min-h-screen bg-gray-100 pt-20 px-4 sm:px-6 lg:px-20 pb-3">
    
    {{-- Toast de éxito al eliminar --}}
    @if (session()->has('message'))
        <div x-data="{ show: true }" 
             x-init="setTimeout(() => show = false, 3000)" 
             x-show="show" 
             x-transition.opacity.duration.500ms
             class="toast toast-center toast-middle z-[99999]">
            <div class="alert alert-success shadow-lg font-bold">
                <span>{{ session('message') }}</span>
            </div>
        </div>
    @endif

    @if($tickets->count() === 0 && empty($wordSearch))
        <div class="flex flex-col items-center justify-center py-20 px-4">
            <div class="bg-gray-50 rounded-full p-6 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300" viewBox="0 0 46 48">
                    <g fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="4">
                        <path stroke-linejoin="round" d="M9 16L34 6l4 10M4 16h40v6c-3 0-6 2-6 5.5s3 6.5 6 6.5v6H4v-6c3 0 6-2 6-6s-3-6-6-6z"/>
                        <path d="M17 25.385h6m-6 6h14"/>
                    </g>
                </svg>
            </div>
            <h3 class="text-xl font-medium text-gray-800 mb-2">Aún no se ha generado ningún ticket</h3>
            <p class="text-gray-500 text-center max-w-sm">
                Utiliza el botón en la barra superior para cargar un archivo con tickets o espere a que los empleados los generen
            </p>
        </div>
    @else
        <div class="w-full overflow-x-auto rounded-box shadow-xl">
            <table id="tickets-table" class="table table-fixed text-sm min-w-full">
                
                <thead class="bg-[#681a32] text-white">
                    <tr>
                        <th class="text-center w-16">ID</th>
                        <th class="text-center w-40">Nombre</th>
                        <th class="text-center w-48">Correo</th>
                        <th class="text-center min-w-60">Descripción</th>
                        <th class="text-center w-40">Área</th>
                        <th class="text-center w-32">Estado actual</th>
                        <th class="text-center w-40">Regresar estado</th>
                        <th class="text-center w-24">Eliminar</th>
                    </tr>
                </thead>

                <tbody class="bg-white text-gray-700 whitespace-nowrap">

                    @foreach ($tickets as $ticket)
                        <tr wire:key="ticket-{{ $ticket->id }}" class="h-14 max-h-14 border-b border-gray-300">
                            <td class="border-r border-gray-300 text-center">{{ $ticket->id }}</td>
                            <td class="border-r border-gray-300 text-center overflow-x-auto">{{ $ticket->nombre }}</td>
                            <td class="border-r border-gray-300 text-center truncate" title="{{ $ticket->correo }}">{{ $ticket->correo }}</td>                        
                            <td class="border-r border-gray-300 text-center overflow-x-auto">
                                {{ $ticket->descripcion }}
                            </td>
                            <td class="border-r border-gray-300 text-center overflow-x-auto">{{ $ticket->area }}</td>
                            
                            {{-- Estado actual --}}
                            <td class="h-14 flex justify-center border-r border-gray-300">
                                <div class="badge badge-outline badge-{{ $ticket->estado_sty }} w-full h-full">
                                    {{ $ticket->estado_txt }}
                                </div>
                            </td>
                            
                            {{-- Regresar estado anterior --}}
                            <td class="h-14 text-center border-r border-gray-300">
                                @if ($ticket->estado == 2)
                                    <button type="button" 
                                            x-on:click="$wire.prepararRegresoEliminaciónTicket({{ $ticket->id }}).then(() => document.getElementById('modalRegresarEstado')?.showModal())"
                                            class="btn w-full h-full btn-warning">
                                        Atendiendo
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" /></svg>
                                    </button>
                                @elseif ($ticket->estado == 1)
                                    <button type="button" 
                                            x-on:click="$wire.prepararRegresoEliminaciónTicket({{ $ticket->id }}).then(() => document.getElementById('modalRegresarEstado')?.showModal())"
                                            class="btn w-full h-full btn-success">
                                        Abierto
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 15l-3-3m0 0l3-3m-3 3h8M3 12a9 9 0 1118 0 9 9 0 01-18 0z" /></svg>
                                    </button>
                                @else
                                    <span class="text-gray-400 text-xs italic">Estado inicial</span>
                                @endif
                            </td>
                            
                            {{-- Botón de eliminar --}}
                            <td class="border-r border-gray-300 text-center p-2">
                                <button type="button" 
                                        class="cursor-pointer text-red-700 hover:text-red-500 hover:scale-150 transition-transform duration-300"
                                        x-on:click="$wire.prepararRegresoEliminaciónTicket({{ $ticket->id }}).then(() => document.getElementById('modalEliminarTicket')?.showModal())">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                    
                    {{-- Rellenar con filas vacías si hay menos de 10 tickets--}}
                    @for ($i = $tickets->count(); $i < 10; $i++)
                        <tr class="h-14">
                            <td colspan="8"></td>
                        </tr>
                    @endfor

                </tbody>
            </table>

            @if($tickets->hasPages())
                <div class="w-full bg-white py-2 px-5 text-gray-700">
                    {{ $tickets->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- Modal regresar al estado anterior --}}
    <x-form.modal id="modalRegresarEstado"
        key="regresar-{{ $selectedTicket?->id ?? 'new' }}-{{ $formKey }}"
        title="Confirmar cambio de estado"
        submit="regresarEstado"
        subbutton="Cancelar"
        button="Aceptar">
        <div class="text-center">
            <p class="text-base text-gray-800 mb-5">
                ¿Estás seguro que deseas editar el estado del ticket <span class="text-imjuve font-bold">{{ $selectedTicket?->id }}</span>
                @if($selectedTicket?->estado === 1)
                    de <span class="font-semibold">Atendiendo</span> a <span class="font-semibold">Abierto</span>?
                @elseif($selectedTicket?->estado === 2)
                    de <span class="font-semibold">Cerrado</span> a <span class="font-semibold">Atendiendo</span>?
                @endif
            </p>
        </div>
    </x-form.modal>

    {{-- Modal eliminar ticket --}}
    <x-form.modal id="modalEliminarTicket"
        key="eliminar-{{ $selectedTicket?->id ?? 'new' }}-{{ $formKey }}"
        title="Confirmar eliminación"
        submit="eliminarTicket"
        subbutton="Cancelar"
        button="Aceptar">
        <div class="text-center">
            <p class="text-base text-gray-800 mb-5">
                ¿Estás seguro que deseas eliminar el ticket <span class="text-imjuve font-bold">{{ $selectedTicket?->id }}</span>?
            </p>
        </div>
    </x-form.modal>

    {{-- Modal cargar BD Tickets --}}
    <x-form.modal id="modalInfoCargarBD"
        key="InfoImportarBD"
        title="{{ $totalTickets == 0 ? 'Restaurar base de datos' : 'Operación bloqueada' }}"
        subtitle="{{ $totalTickets == 0 ? 'Antes de subir tu archivo, verifica lo siguiente:' : '' }}"
        button="{{ $totalTickets == 0 ? 'Subir archivo' : 'Aceptar' }}" 
        subbutton="{{ $totalTickets == 0 ? 'Cancelar' : '' }}"
        label="{{ $totalTickets == 0 ? 'archivoBD' : '' }}"
        target="fileDB"
        message="Validando archivo">
        
        <div class="text-center">
            
            {{-- BD con tickets --}}
            @if($totalTickets > 0)
                <p class="text-base text-gray-800 font-medium">
                    Actualmente existen <span class="font-bold text-gray-900">{{ $totalTickets }}</span> tickets registrados <br><br>
                </p>
                <p class="text-base text-gray-800">
                    Para evitar pérdidas de información y conflictos entre los tickets, <strong>no se puede importar</strong> un archivo sobre una base de datos activa<br><br>
                    Debes utilizar el botón <span class="font-bold text-imjuve">Eliminar base de datos</span> para vaciar el sistema antes de subir un respaldo
                </p>
            
            {{-- BD vacía --}}
            @else
                <ul class="text-sm text-gray-700 space-y-3 bg-gray-100 p-4 rounded-xl border border-gray-100 text-left">
                    <li class="flex items-start gap-2">
                            <span class="text-blue-500 mt-1">•</span>
                            <span>Sube la base de datos en formato <strong>SQL</strong> (recomendado) o en <strong>Excel</strong>, generado previamente por la plataforma</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-500 mt-0.5">•</span>
                            <span>El Excel debe tener exactamente las <strong>columnas originales</strong> del sistema (id, nombre, correo, área, tipo, descripción, comentarios, estado, fechas, personal)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-blue-500 mt-0.5">•</span>
                            <span>Los datos relacionado con el cierre o atención (fechas y personal) <strong>pueden omitirse</strong> si el ticket no ha sido procesado</span>
                        </li>
                </ul>
                <p class="text-sm text-gray-500 mt-4 text-center">Formatos admitidos: .sql, .xlsx, .xls, .csv</p>
                
                <input type="file" id="archivoBD" wire:model.live="fileDB" onchange="document.getElementById('modalInfoCargarBD').close();" class="hidden" accept=".xlsx,.xls,.csv,.sql">
            @endif
        </div>
    </x-form.modal>

    {{-- Modal error formato --}}
    <x-form.modal id="modalErrorFormatoBD"
        key="ErrorFormatoBD"
        title="Formato incorrecto"
        button="Aceptar">
        <div class="py-2 text-center">
            <p class="text-center text-red-600 font-bold text-base mb-2">
                {{ $errorMessage }}
            </p>
            <p class="text-center text-gray-700 mt-4">
                Revisa las instrucciones para cargar el archivo, modifícalo y vuelve a intentarlo
            </p>
        </div>
    </x-form.modal>

    {{-- Modal datos incompletos --}}
    <x-form.modal id="modalDatosIncompletosBD"
        key="DatosIncompletosBD"
        button="Aceptar"
        title="Tickets incompletos detectados"
        subtitle="Faltan datos obligatorios en los siguientes registros:">
        <div class="py-4 text-center">
            <div class="max-h-60 overflow-y-auto rounded-xl border border-gray-100 shadow-inner">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 font-medium">Ticket ID</th>
                            <th class="px-4 py-3 font-medium">Dato faltante</th>
                        </tr> 
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($datosIncompletos as $item)
                            <tr class="hover:bg-red-50/30 transition-colors">
                                <td class="px-4 py-3 font-semibold text-gray-800">{{ $item['dato'] }}</td>
                                <td class="px-4 py-3"><span class="text-red-600 font-bold text-xs tracking-wider uppercase">{{ $item['error'] }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center py-4 text-gray-500">Todo en orden</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p class="text-sm text-gray-700 mt-6">
                Revisa las instrucciones para cargar el archivo, modifícalo y vuelve a intentarlo
            </p>
        </div>
    </x-form.modal>
</div>