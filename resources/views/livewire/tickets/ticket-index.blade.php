<div class="flex items-center min-h-screen bg-gray-100 pt-20 px-20 pb-3">

    {{-- Tabla de tickets --}}
    <div class="min-w-[1200px] overflow-x-auto rounded-box w-full shadow-xl">
        <table id="tickets-table" class="table table-fixed text-xs">
            
            <!-- head -->
            <thead class="bg-[#681a32] text-white">
                <tr>
                    <th colspan="id" class="text-center w-13">ID</th>
                    <th colspan="nombre" class="text-center w-30">Nombre</th>
                    <th colspan="correo" class="text-center w-40">Correo</th>
                    <th colspan="descripcion" class="text-center min-w-70">Descripción</th>
                    <th colspan="tipo" class="text-center w-25 h-10 p-1">
                        <x-form.dropdown-checkbox 
                            title="Tipo"
                            field="tipo" 
                            :filters="$tipos"
                        />
                    </th>
                    <th colspan="area" class="text-center w-25 h-10 p-1">
                        <x-form.dropdown-checkbox 
                            title="Área" 
                            field="area" 
                            :filters="$areas"
                        />
                    </th>
                    <th colspan="estado" class="text-center w-35 h-10 p-1">
                        <x-form.dropdown-checkbox 
                            title="Estado" 
                            field="estado" 
                            :filters="$estados"
                            filterType="id"
                        />
                    </th>
                    <th colspan="creado" class="text-center w-35 h-10 p-1">
                        <x-form.dropdown-date 
                            title="Creado"
                            checked="checked"
                            field="created_at" 
                        />
                    </th>
                    <th colspan="creado" class="text-center w-35 h-10 p-1">
                        <x-form.dropdown-date 
                            title="Atendido" 
                            field="atendido_at" 
                        />
                    </th>
                    <th colspan="cerrado" class="text-center w-35 h-10 p-1">
                        <x-form.dropdown-date 
                            title="Cerrado" 
                            field="updated_at" 
                        />
                    </th>
                </tr>
            </thead>

            <!-- body -->
            <tbody class="bg-white text-gray-700 whitespace-nowrap">

                @foreach ($tickets as $ticket)
                    <tr class="h-14 max-h-14 border-b border-gray-300">
                        <td class="border-r border-gray-300 text-center">{{ $ticket->id }}</td>
                        <td class="border-r border-gray-300 text-center truncate">{{ $ticket->nombre }}</td>
                        <td class="border-r border-gray-300 truncate">{{ $ticket->correo }}</td>
                        <td class="border-r border-gray-300 overflow-x-auto">{{ $ticket->descripcion }}</td>
                        <td class="border-r border-gray-300 overflow-x-auto">{{ $ticket->tipo }}</td>
                        <td class="border-r border-gray-300 overflow-x-auto">{{ $ticket->area }}</td>
                        <td class="h-14 flex justify-center border-r border-gray-300">
                            @if( $ticket->estado == $numEstados-1 )
                                <div class="badge badge-outline badge-{{ $ticket->estado_sty }} w-full h-full">{{ $ticket->estado_txt }}</div>
                            @else
                                @guest
                                    <div class="badge badge-outline badge-{{ $ticket->estado_sty }} w-full h-full">{{ $ticket->estado_txt }}</div>
                                @endguest

                                @auth
                                    <button wire:key="{{ $ticket->id }}"
                                            wire:click="ticketProgress({{ $ticket->id }})"
                                            wire:confirm.prompt='¿Estás seguro de que deseas cambiar el estado del ticket {{ $ticket->id }}?
                                                                 \nIngresa la palabra "{{ $ticket->estado_sigtxt }}" para confirmar|{{ $ticket->estado_sigtxt }}'
                                            class="btn btn-{{ $ticket->estado_sty }} w-full h-full">
                                        {{ $ticket->estado_txt }}
                                    </button>
                                @endauth
                            @endif
                        </td>
                        <td class="border-r border-gray-300 text-center">{{ $ticket->created_at }}</td>
                        <td class="border-r border-gray-300 text-center">{{ $ticket->atendido_at }}</td>
                        <td class="text-center">@if($ticket->estado == $numEstados-1){{ $ticket->updated_at }}@endif</td>
                    </tr>
                @endforeach
                
                {{-- Rellenar con filas vacías si hay menos de 10 tickets --}}
                @for ($i = $tickets->count(); $i < 10; $i++)
                    <tr class="h-14">
                        <td colspan="10"></td>
                    </tr>
                @endfor

            </tbody>
        </table>

        <!-- Paginación -->
        <div class="w-full bg-white shadow-xl py-2 px-5 text-gray-700">
            {{ $tickets->links() }}
        </div>

    </div>

    {{-- Elementos ocultos para mantener los estilos de DaisyUI --}}
    {{-- (DaisyUI aplica estilos solo si detecta estos elementos en el DOM) --}}
    <div class="badge badge-outline badge-error" style="display: none;"></div>
    <div class="badge badge-outline badge-warning" style="display: none;"></div>
    <div class="badge badge-outline badge-success" style="display: none;"></div>
    <button class="btn btn-error" style="display: none;"></button>
    <button class="btn btn-warning" style="display: none;"></button>
    <button class="btn btn-success" style="display: none;"></button>

    {{--
    <!-- jQuery (necesario para colResizable) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- colResizable -->
    <script src="https://cdn.jsdelivr.net/npm/colresizable/colResizable-1.6.min.js"></script>
    <script>
        document.addEventListener('livewire:navigated', function () {
            $('#tickets-table').colResizable({
                liveDrag: true,
                gripInnerHtml: "<div class='grip'></div>",
                draggingClass: "dragging",
                resizeMode: 'fit'
            });
        });
    </script>
    --}}

</div>
