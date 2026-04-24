<div class="min-h-screen bg-gray-100 pt-20 px-4 sm:px-6 lg:px-20 pb-3">
    <button type="button" id="btn-fantasma-agregar" class="hidden" wire:click="prepararAdicion"></button>
    {{-- Toast de éxito en el CRUD de empleados --}}
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

    @if($empleados->isEmpty() && empty($wordSearch))
        <div class="flex flex-col items-center justify-center py-20 px-4">
            <div class="bg-gray-50 rounded-full p-6 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h3 class="text-xl font-medium text-gray-800 mb-2">Aún no se ha cargado ningún correo para validar</h3>
            <p class="text-gray-500 text-center max-w-sm">
                Utiliza los botones en la barra superior para importar un archivo Excel o agregar empleados manualmente
            </p>
        </div>
    @else
        <div class="w-full overflow-x-auto rounded-box shadow-xl">            
            <table class="table table-fixed text-base w-full">
                <thead class="bg-[#681a32] text-white">
                    <tr>
                        <th class="text-center w-20">ID</th>
                        <th class="text-center w-1/3">Nombre del empleado</th>
                        <th class="text-center w-1/3">Correo electrónico</th>
                        <th class="text-center w-40">Acciones</th>
                    </tr>
                </thead>

                <tbody class="bg-white text-gray-700 whitespace-nowrap">
                    @foreach($empleados as $index => $empleado)
                        <tr wire:key="empleado-{{ $empleado->id }}" class="h-14 max-h-14 border-b border-gray-300 hover:bg-gray-100 transition-colors">                            
                            <td class="border-r border-gray-300 text-center font-semibold">{{ $empleado->id }}</td>
                            <td class="border-r border-gray-300 text-center truncate px-4">{{ $empleado->nombre }}</td>
                            <td class="border-r border-gray-300 text-center truncate px-4">{{ $empleado->correo }}</td>
                            <td class="h-14 flex justify-center items-center gap-10">
                                {{-- Botón Modificar --}}
                                <button class="cursor-pointer text-blue-700 hover:text-blue-500 hover:scale-150 transition-transform duration-300" type="button"
                                        x-on:click="$wire.prepararEdicionEliminacion({{ $empleado->id }}).then(() => document.getElementById('modalEditarEmpleado').showModal())">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                </button>
                                {{-- Botón Eliminar --}}
                                <button class="cursor-pointer text-red-700 hover:text-red-500 hover:scale-150 transition-transform duration-300" type="button"
                                        x-on:click="$wire.prepararEdicionEliminacion({{ $empleado->id }}).then(() => document.getElementById('modalEliminarEmpleado').showModal())">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach

                    {{-- El relleno de filas --}}
                    @for ($i = $empleados->count(); $i < 10; $i++)
                        <tr class="h-14">
                            <td colspan="4"></td>
                        </tr>
                    @endfor
                </tbody>
            </table>

            {{-- Paginación --}}
            @if($empleados->hasPages())
                <div class="w-full bg-white shadow-xl py-2 px-5 text-gray-700">
                    {{ $empleados->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- Modal de instrucciones de importación --}}
    <x-form.modal id="modalInfoImportarEmpleados"
        key="InfoImportarExcel"
        title="Instrucciones de importación"
        subtitle="Antes de subir tu archivo, verifica lo siguiente:"
        button="Subir archivo"
        subbutton="Cerrar"
        label="archivoExcel">
        <div>
            <ul class="text-base text-gray-700 space-y-3 bg-gray-100 p-4 rounded-xl border border-gray-100 text-left">
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Las columnas deben llamarse estrictamente <strong>Nombre</strong> y <strong>Correo</strong></span>
                </li>
                <li class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <span>Todos los empleados deben tener un <strong>nombre</strong> y un <strong>correo electrónico institucional</strong> asociados</span>
                </li>
            </ul>
            <p class="text-sm text-gray-500 mt-4 text-center">
                Formatos admitidos: .xlsx, .xls, .csv
            </p>
        </div>
        <input type="file" id="archivoExcel" wire:model.live="fileExcel" onchange="document.getElementById('modalInfoImportarEmpleados').close();" class="hidden" accept=".xlsx,.xls,.csv">
    </x-form.modal>

    {{-- Modal de error de formato --}}
    <x-form.modal id="modalErrorFormato"
        key="ErrorFormatoExcel"
        title="Formato incorrecto"
        button="Aceptar"
        target="fileExcel"
        message="Validando archivo">
        <div class="text-center">
            <p class="text-center text-gray-800 mb-5">
                {{ $errorMessage }}
            </p>
            <p class="text-center text-gray-700 mt-6">
                Revisa las instrucciones de importación, modifica el archivo y vuelve a intentarlo
            </p>
        </div>
    </x-form.modal>

    {{-- Modal de datos incompletos --}}
    <x-form.modal id="modalDatosIncompletos"
        key="DatosIncompletosExcel"
        button="Aceptar"
        title="Datos incompletos"
        subtitle="Falta información de los siguientes empleados:">
        <div class="text-center">
            <div class="max-h-60 overflow-y-auto rounded-xl border border-gray-100 shadow-inner">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-gray-600 sticky top-0">
                        <tr>
                            <th class="px-4 py-3 font-medium">Dato encontrado</th>
                            <th class="px-4 py-3 font-medium">Dato faltante</th>
                        </tr> 
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($empleadosIncompletos as $item)
                            <tr class="hover:bg-red-50/30 transition-colors">
                                <td class="px-4 py-3 font-semibold text-gray-800">
                                    {{ $item['dato'] }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-red-600 font-bold text-xs tracking-wider uppercase">
                                        {{ $item['error'] }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="text-center py-4 text-gray-500">Ningún dato incompleto</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <p class="text-sm text-gray-700 mt-6">
                Revisa que todos los empleados tengan un correo y un nombre asociados, modifica el archivo y vuelve a intentarlo
            </p>
        </div>
    </x-form.modal>

    {{-- Modal para agregar empleado --}}
    <x-form.modal id="modalAgregarEmpleado"
        key="AgregarEmpleadoManual"
        title="Agregar nuevo empleado"
        submit="agregarEmpleado"
        button="Aceptar"
        subbutton="Cancelar"
        target="agregarEmpleado"
        message="Validando datos"
        wire:ignore.self>
        <div class="text-left flex flex-col space-y-4.5" wire:key="container-add-{{ $formKey }}">            
            <x-form.input
                legend="Nombre completo" 
                model="nForm.nombre" 
                type="text"
                placeholder="Ej. ARCHIVALDO PÉREZ GÓMEZ"
            />
            <x-form.input 
                legend="Correo institucional" 
                model="eForm.correo"
                type="text"
                placeholder="Ej. usuario@imjuventud.gob.mx"
            />
        </div>
    </x-form.modal>

    {{-- Modal para editar empleado --}}
    <x-form.modal id="modalEditarEmpleado"
        key="EditarEmpleadoModal"
        title="Editar datos de empleado"
        submit="actualizarEmpleado"
        button="Aceptar"
        subbutton="Cancelar"
        target="actualizarEmpleado"
        message="Validando datos"
        wire:ignore.self>

        <div class="text-left flex flex-col gap-6" wire:key="container-edit-{{ $empleadoId }}-{{ $formKey }}">
            <x-form.input
                legend="Nombre completo" 
                model="nForm.nombre" 
                type="text"
            />
            <x-form.input 
                legend="Correo institucional" 
                model="eForm.correo"
                type="text"
            />
        </div>
    </x-form.modal>

    {{-- Modal para confirmar eliminación --}}
    <x-form.modal id="modalEliminarEmpleado"
        key="EliminarEmpleadoModal"
        title="Eliminar empleado"
        submit="eliminarEmpleado"
        button="Confirmar"
        subbutton="Cancelar">
        
        <div class="text-center" wire:key="container-delete-{{ $empleadoId }}-{{ $formKey }}"> 
            <p class="text-base text-gray-800 mb-5">
                <br>¿Estás seguro de que deseas eliminar a <br>
                <strong class="text-gray-800 text-semibold">{{ $nForm->nombre }}</strong>?
            </p>
            <p class="text-base text-gray-800 mb-5">Esta acción no se puede deshacer y el empleado no podrá levantar más tickets</p>
        </div>
    </x-form.modal>
</div>
