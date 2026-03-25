<div class="min-h-screen bg-gray-100 pt-20 px-4 sm:px-6 lg:px-20 pb-3 flex items-center">

    {{-- Tabla de tickets --}}
    <div class="w-full overflow-x-auto rounded-box w-full shadow-xl">
        <table id="tickets-table" class="table table-fixed text-xs min-w-[1200px]">
            
            <!-- head -->
            <thead class="bg-[#681a32] text-white">
                <tr>
                    <th colspan="id" class="text-center w-13">ID</th>
                    <th colspan="nombre" class="text-center w-30">Nombre</th>
                    <th colspan="correo" class="text-center w-40">Correo</th>
                    <th colspan="descripcion" class="text-center min-w-70">Descripción</th>
                    <th colspan="comentarios" class="text-center min-w-70">Comentarios</th>
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
                    <th colspan="cerrado" class="text-center w-35 h-10 p-1">
                        <x-form.dropdown-date 
                            title="Cerrado" 
                            field="cerrado_at" 
                        />
                    </th>
                </tr>
            </thead>

            <!-- body -->
            <tbody class="bg-white text-gray-700 whitespace-nowrap">

                @foreach ($tickets as $ticket)
                    <tr class="h-14 max-h-14 border-b border-gray-300">
                        <td class="border-r border-gray-300 text-center">{{ $ticket->id }}</td>
                        <td class="border-r border-gray-300 text-center overflow-x-auto">{{ $ticket->nombre }}</td>
                        <td class="border-r border-gray-300 truncate">{{ $ticket->correo }}</td>
                        <x-form.interactive-td
                            content="{{ $ticket->descripcion }}"
                            wire:key="{{ $ticket->id }}"
                            x-on:click="$wire.findTicket({{ $ticket->id }}).then(() => document.getElementById('modalDescripcion')?.showModal())"
                        />
                        <x-form.interactive-td
                            content="{{ $ticket->comentarios }}"
                            wire:key="{{ $ticket->id }}"
                            x-on:click="$wire.findTicket({{ $ticket->id }}).then(() => document.getElementById('{{ auth()->check() ? 'modalCA' : 'modalCE' }}')?.showModal())"
                            :noGlow="empty($ticket->comentarios) && !auth()->check()"
                        />
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
                                    <button type="button"
                                            x-on:click="$wire.prepareStatusChange({{ $ticket->id }}).then(() => document.getElementById('modalCambioEstado')?.showModal())"
                                            class="btn btn-{{ $ticket->estado_sty }} w-full h-full">
                                        {{ $ticket->estado_txt }}
                                    </button>
                                @endauth
                            @endif
                        </td>
                        <td class="border-r border-gray-300 text-center">{{ $ticket->created_at }}</td>
                        <td class="border-r border-gray-300 text-center">{{ $ticket->cerrado_at }}</td>
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


    {{-- Modals --}}

    {{-- Modal de descripcion --}}
    <x-form.modal id="modalDescripcion"
        key="{{ $selectedTicket?->id ?? 'new' }}"
        button="Cerrar">
        <x-form.textarea 
            legend="Descripción del ticket"
            legendAccent="{{ $selectedTicket?->id ?? '' }}"
            key="modelD{{ $selectedTicket?->id ?? 'new' }}"
            model="tForm.descripcion"
            readonly
        />
    </x-form.modal>

    {{-- Modal de comentario empleado --}}
    <x-form.modal id="modalCE"
        key="{{ $selectedTicket?->id ?? 'new' }}"
        button="Cerrar">
        <x-form.textarea 
            legend="Comentarios del ticket"
            legendAccent="{{ $selectedTicket?->id ?? '' }}"
            model="cForm.comentarios"
            readonly
        />
    </x-form.modal>

    {{-- Modal de comentarios admin --}}
    <x-form.modal id="modalCA"
        key="{{ $selectedTicket?->id ?? 'new' }}"
        submit="editComments({{ $selectedTicket?->id }})"
        subbutton="Cerrar"
        button="Editar">
        <x-form.textarea 
            legend="Comentarios del ticket"
            legendAccent="{{ $selectedTicket?->id ?? '' }}"
            model="cForm.comentarios"
            wire:key="txt-ca-{{ $selectedTicket?->id ?? 'new' }}-{{ $formKey }}"
        />
    </x-form.modal>

    {{-- Modal de cambio de estado del ticket --}}
    <x-form.modal id="modalCambioEstado"
        key="estado-{{ $selectedTicket?->id ?? 'new' }}-{{ $formKey }}"
        title="Confirmar cambio de estado"
        submit="confirmStatus"
        subbutton="Cancelar"
        button="Aceptar"
        target="confirmStatus"
        message="Validando palabra"
        wire:ignore.self>
        <fieldset class="fieldset relative">
            <legend class="fieldset-legend text-legend text-center">
                ¿Estás seguro de modificar el estado del ticket
                <span class="text-imjuve">{{ $selectedTicket?->id ?? '' }}</span>?
            </legend>
            <div class="form-control w-full mt-2">
                <input type="text" 
                    wire:model="sForm.confirmationWord"
                    wire:key="txt-estado-{{ $selectedTicket?->id ?? 'new' }}-{{ $formKey }}"
                    class="input input-bordered w-full @error('sForm.confirmationWord') border-red-500 border-2 @enderror"
                    placeholder="Ingresa la palabra '{{ $sForm->expectedWord ?? '' }}'">
            </div>
            @error('sForm.confirmationWord') 
                <p class="absolute -bottom-5 right-0 font-bold text-red-500 text-xs">{{ $message }}</p>
            @enderror
        </fieldset>
    </x-form.modal>


    {{-- Elementos ocultos para mantener los estilos de DaisyUI --}}
    {{-- (DaisyUI aplica estilos solo si detecta estos elementos en el DOM) --}}
    <div class="badge badge-outline badge-error" style="display: none;"></div>
    <div class="badge badge-outline badge-warning" style="display: none;"></div>
    <div class="badge badge-outline badge-success" style="display: none;"></div>
    <button class="btn btn-error" style="display: none;"></button>
    <button class="btn btn-warning" style="display: none;"></button>
    <button class="btn btn-success" style="display: none;"></button>

</div>


