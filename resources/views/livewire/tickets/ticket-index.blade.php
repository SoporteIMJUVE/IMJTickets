<div class="flex justify-center items-center min-h-screen bg-gray-100 pt-20 px-20 pb-3">

    {{-- Tabla de tickets --}}
    <div class="overflow-x-auto rounded-box w-full shadow-xl">
        <table id="tickets-table" class="table table-fixed text-xs">

            <!-- head -->
            <thead class="bg-[#681a32] text-white">
                <tr>
                    <th colspan="id" class="text-center w-13">ID</th>
                    <th colspan="id" class="text-center w-30">Reporto</th>
                    <th colspan="correo" class="text-center w-40">Correo</th>
                    <th colspan="descripcion" class="text-center">Descripción</th>
                    <th colspan="tipo" class="text-center w-25">Tipo</th>
                    <th colspan="area" class="text-center w-25">Área</th>
                    <th colspan="estado" class="text-center w-35">Estado</th>
                    <th colspan="creatdo" class="text-center w-35">Creado</th>
                    <th colspan="atendido" class="text-center w-35">Atendido</th>
                    <th colspan="cerrado" class="text-center w-35">Cerrado</th>
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
                        <td class="border-r border-gray-300 text-center truncate">{{ $ticket->tipo }}</td>
                        <td class="border-r border-gray-300 text-center truncate">{{ $ticket->area }}</td>
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
                        <td class="border-r border-gray-300 text-center">{{ $ticket->updated_at }}</td>
                        <td class="text-center">{{ $ticket->updated_at }}</td>
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
