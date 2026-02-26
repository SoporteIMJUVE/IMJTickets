<div class="min-h-screen bg-gray-100 pt-20 px-4 sm:px-6 lg:px-20 pb-3 text-center" 
     x-data="{ showToast: false, toastMessage: '' }" 
     x-on:mostrar-toast.window="
        document.getElementById('modal-agregar-empleado').checked = false;
        document.getElementById('modal-editar-empleado').checked = false;
        document.getElementById('modal-eliminar-empleado').checked = false;
        toastMessage = $event.detail.mensaje; 
        showToast = true;
        setTimeout(() => showToast = false, 3000);"
     x-on:empleado-agregado.window="showToast = false">
    @if($empleados && $empleados->count() > 0)
        <table class="w-full bg-white rounded-box shadow-xl overflow-hidden">
            <thead class="bg-[#681a32] text-white">
                <tr>
                    <th class="text-center py-2">Nombre de empleado</th>
                    <th class="text-center">Correo electrónico</th>
                    <th class="text-center w-60">Acciones</th>
                </tr>
            </thead>

            <tbody class="text-gray-700">
                @foreach($empleados as $index => $empleado)
                    <tr class="hover:bg-gray-50 border-b border-gray-200 transition-colors">
                        <td class="py-4">{{ $empleado->nombre }}</td>
                        <td>{{ $empleado->correo }}</td>
                        <td class="flex justify-center gap-5 py-4">
                            {{-- Botón Editar --}}
                            <button class="btn btn-ghost btn-xs text-blue-600 hover:bg-blue-50 hover:brightness-90 transition-all" wire:click="editarEmpleado('{{ $empleado->correo }}', 'abrir-modal-edicion')">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>

                            {{-- Botón Eliminar --}}
                            <button class="btn btn-ghost btn-xs text-red-600 hover:bg-red-50 hover:brightness-90 transition-all" wire:click="prepararEliminacion('{{ $empleado->correo }}', '{{ $empleado->nombre }}')"
                                    onclick="document.getElementById('modal-eliminar-empleado').checked = true">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" >
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

            <div class="px-4 py-3 border-t border-gray-100">
                {{ $empleados->links() }}
            </div>

    @else
        <div class="flex flex-col items-center justify-center py-20 px-4">
            <div class="bg-gray-50 rounded-full p-6 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                </svg>
            </div>
            <h3 class="text-xl font-medium text-gray-800 mb-2">Aún no se ha cargado ningún correo para validar</h3>
            <p class="text-gray-500 text-center max-w-sm">
                Utiliza los botones en la barra superior para importar un archivo Excel o agregar empleados individualmente
            </p>
        </div>
    @endif

    {{-- Modal de Instrucciones de Importación --}}
    <input type="checkbox" id="modal-importar" class="modal-toggle" />
    <div class="modal">
        <div class="modal-box relative bg-white p-0 overflow-hidden w-72 sm:w-120">        
            <div class="p-6 pb-2 text-center">
                <h3 class="text-xl font-bold flex items-center justify-center gap-2" style="color: #641332;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Instrucciones de importación
                </h3>
                <p class="text-sm text-gray-800 mt-2">
                    Asegúrate de que los nombres y el orden de la columnas dentro del archivo Excel coincidan con el siguiente formato:
                </p>
            </div>
            <div class="px-6 py-3">
                <div class="grid grid-cols-2 gap-3 bg-gray-50 p-1 rounded-xl border border-gray-100">
                    <div class="flex-1 items-center gap-2 text-gray-700 font-medium">Nombre</div>
                    <div class="flex-1 items-center gap-2 text-gray-700 font-medium">Correo</div>
                </div>
            </div>              
            <div class="p-6 pt-2 flex justify-end gap-3">
                <label for="modal-importar" class="btn btn-neutral btn-outline">
                    Cancelar
                </label>
                <label for="modal-importar" 
                       onclick="document.getElementById('input-excel').click()" 
                       class="btn btn-imjuve border-none text-white hover:brightness-85 transition-all shadow-sm">
                    Seleccionar
                </label>
            </div>              
        </div>
    </div>

    {{-- Modal de error al procesar Excel --}}
    <input type="file" id="input-excel" wire:model="fileExcel" class="hidden" accept=".xlsx,.xls,.csv">
    <div x-data="{ openError: false }" 
         x-on:mostrar-error-excel.window="openError = true">
        <input type="checkbox" id="modal-error-columnas" class="modal-toggle" x-model="openError" />
        <div class="modal modal-bottom sm:modal-middle overflow-hidden">
            <div class="modal-box relative bg-white p-0 overflow-hidden w-72 sm:w-120">
                <div class="p-6 pb-2 text-center">
                    <h3 class="text-xl font-bold flex items-center justify-center gap-2 text-yellow-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        Formato de archivo incorrecto
                    </h3>
                    <p class="text-sm text-gray-500 mt-2">
                        No se lograron detectar las columnas necesarias en el archivo Excel.
                    </p>
                    <p class="text-sm text-gray-500 mt-2">
                        Asegúrate de que la primera fila contenga exactamente los encabezados:
                        <span class="font-bold text-gray-800">Nombre</span> y 
                        <span class="font-bold text-gray-800">Correo</span>.
                    </p>
                    <p class="text-sm text-gray-500 mt-2">
                        Revisa el formato del archivo e inténtalo nuevamente.
                    </p>
                </div>
                <div class="p-6 pt-2">
                    <button @click="openError = false" 
                            class="btn w-full border-none text-white hover:brightness-90 transition-all bg-yellow-600 hover:bg-yellow-700 shadow-sm">
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div wire:loading.class="opacity-100 visible" wire:target="fileExcel" class="opacity-0 invisible transition-all duration-500 fixed inset-0 backdrop-blur-sm z-40 pointer-events-none"></div>
    <div wire:loading.class="opacity-100 visible" wire:target="fileExcel" class="opacity-0 invisible transition-all duration-500 fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 bg-white border shadow-xl rounded-lg p-6 flex flex-col items-center gap-2 pointer-events-auto">
        <span class="loading loading-bars loading-xl text-primary"></span>
        <span class="font-semibold text-gray-700">Cargando archivo</span>
    </div>

    {{-- Modal de Agregar nuevo empleado --}}
    <input type="checkbox" id="modal-agregar-empleado" class="modal-toggle" />
    <div class="modal">
        <div class="modal-box bg-white">
            <h3 class="font-bold text-lg text-[#641332]">Agregar nuevo empleado</h3>
            <div id="modal-eliminar-errores" class="py-2 flex flex-col gap-4">
                <div class="relative mb-2">
                    <input type="text" wire:model="crearNombre" 
                           class="input input-bordered w-full @error('crearNombre') border-red-500 border-3 @enderror" 
                           placeholder="Nombre completo" />
                    @error('crearNombre')
                        <p class="absolute -bottom-4 right-0 font-bold text-red-500 text-xs">{{ $message }}</p>
                    @enderror
                </div>
                <div class="relative mb-2">
                    <input type="email" wire:model="crearCorreo"
                           class="input input-bordered w-full @error('crearCorreo') border-red-500 border-3 @enderror" 
                           placeholder="Correo institucional" />
                    @error('crearCorreo')
                        <p class="absolute -bottom-4 right-0 font-bold text-red-500 text-xs">{{ $message }}</p>
                    @enderror
                </div>           
            </div>
            <div class="modal-action">
                <label for="modal-agregar-empleado" wire:target="agregarEmpleado" wire:loading.remove class="btn btn-neutral btn-outline">Cancelar</label>
                <button wire:click="agregarEmpleado" wire:loading.remove class="btn btn-imjuve hover:brightness-85 text-white btn-ghost">
                    Guardar
                </button>
            </div>
        </div>
    </div>

    <div wire:loading.class="opacity-100 visible" wire:target="agregarEmpleado" class="opacity-0 invisible transition-all duration-500 fixed inset-0 backdrop-blur-sm z-[1000] pointer-events-none"></div>
    <div wire:loading.class="opacity-100 visible" wire:target="agregarEmpleado" class="opacity-0 invisible transition-all duration-500 fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-[1010] bg-white border shadow-xl rounded-lg p-6 flex flex-col items-center gap-2 pointer-events-auto">
        <span class="loading loading-bars loading-xl text-primary"></span>
        <span class="font-semibold text-gray-700">Validando datos</span>
    </div>

    <div x-show="showToast" 
     x-transition.opacity.duration.400ms 
     class="toast toast-center toast-middle z-[1100]">
    
    <div class="alert alert-success shadow-2xl">
        {{-- Aquí se inserta el mensaje dinámico --}}
        <span class="font-bold text-lg text-white" x-text="toastMessage"></span>
    </div>
</div>

    {{-- Modal de Editar empleado --}}
    <input type="checkbox" id="modal-editar-empleado" class="modal-toggle" />
    <div class="modal" x-data="{}" x-on:abrir-modal-edicion.window="document.getElementById('modal-editar-empleado').checked = true">
        <div class="modal-box bg-white">
            <h3 class="font-bold text-lg text-[#641332]">Editar datos de empleado</h3>
            <div class="py-2 flex flex-col gap-4">
                <div class="relative mb-2">
                    <input type="text" wire:model="editarNombre" 
                           class="input input-bordered w-full @error('editarNombre') border-red-500 border-3 @enderror"/>
                    @error('editarNombre')
                        <span class="absolute -bottom-4 right-0 font-bold text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>
                <div class="relative mb-2">
                    <input type="text" wire:model="editarCorreo" 
                           class="input input-bordered w-full @error('editarCorreo') border-red-500 border-3 @enderror"/>
                    @error('editarCorreo')
                        <span class="absolute -bottom-4 right-0 font-bold text-red-500 text-xs">{{ $message }}</span>
                    @enderror
                </div>
            </div>
            <div class="modal-action">
                <label for="modal-editar-empleado" wire:loading.remove class="btn btn-neutral btn-outline">Cancelar</label>
                <button wire:click="actualizarEmpleado" wire:loading.remove class="btn btn-imjuve hover:brightness-85 text-white btn-ghost">
                    Actualizar
                </button>
            </div>
        </div>
    </div>

    <div wire:loading.class="opacity-100 visible" wire:target="actualizarEmpleado" class="opacity-0 invisible transition-all duration-500 fixed inset-0 backdrop-blur-sm z-[1000] pointer-events-none"></div>
    <div wire:loading.class="opacity-100 visible" wire:target="actualizarEmpleado" class="opacity-0 invisible transition-all duration-500 fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-[1010] bg-white border shadow-xl rounded-lg p-6 flex flex-col items-center gap-2 pointer-events-auto">
        <span class="loading loading-bars loading-xl text-primary"></span>
        <span class="font-semibold text-gray-700">Validando datos</span>
    </div>

    <div x-show="showToast" x-transition.opacity.duration.400ms class="toast toast-center toast-middle z-[1100]">
        <div class="alert alert-success shadow-2xl">
            <span class="font-bold text-lg text-white" x-text="toastMessage"></span>
        </div>
    </div>

    {{-- Modal de Confirmación de Eliminación --}}
    <input type="checkbox" id="modal-eliminar-empleado" class="modal-toggle" />
    <div class="modal">
        <div class="modal-box relative bg-white p-0 overflow-hidden w-72 sm:w-120">        
            <div class="p-6 pb-2 text-center">
                <h3 class="text-xl font-bold flex items-center justify-center gap-2 text-[#641332]">
                    Confirmar eliminación
                </h3>
                <p class="text-sm text-gray-800 mt-2">
                    ¿Está seguro de que desea eliminar al empleado(a)
                        <span class="font-bold text-gray-800">{{ $empleadoAEliminar['nombre'] ?? '' }}</span>?
                </p>
                <p class="text-sm text-gray-800 mt-2">
                    Esta acción no se puede deshacer
                </p>
            </div>              
            <div class="p-6 pt-2 flex justify-end gap-3">
                <label for="modal-eliminar-empleado" class="btn btn-neutral btn-outline">
                    Cancelar
                </label>
                <button wire:click="confirmarEliminacion" class="btn btn-imjuve hover:brightness-85 text-white btn-ghost" onclick="document.getElementById('modal-eliminar-empleado').checked = false">
                    Eliminar
                </button>
            </div>              
        </div>
    </div>
</div>   
