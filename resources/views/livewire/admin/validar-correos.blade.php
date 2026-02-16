<div class="min-h-screen bg-gray-100 pt-20 px-4 sm:px-6 lg:px-20 pb-3">
    @if($empleados && $empleados->count() > 0)
        <table class="w-full bg-white rounded-box shadow-xl overflow-hidden">
            <thead class="bg-[#681a32] text-white">
                <tr>
                    <th class="text-center w-20 py-4">#</th>
                    <th class="text-left">Nombre del Empleado</th>
                    <th class="text-left">Correo Electrónico</th>
                    <th class="text-center w-40">Acciones</th>
                </tr>
            </thead>

            <tbody class="text-gray-700">
                @foreach($empleados as $index => $empleado)
                    <tr class="hover:bg-gray-50 border-b border-gray-200 transition-colors">
                        <td class="text-center font-semibold">{{ $index + 1 }}</td>
                        <td class="py-4">{{ $empleado->nombre }}</td>
                        <td>{{ $empleado->correo }}</td>
                        <td class="flex justify-center gap-3 py-4">
                            {{-- Botón Modificar --}}
                            <button class="btn btn-ghost btn-xs text-blue-600 hover:bg-blue-50 hover:brightness-90 transition-all tooltip" data-tip="Editar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                            </button>

                            {{-- Botón Eliminar --}}
                            <button class="btn btn-ghost btn-xs text-red-600 hover:bg-red-50 hover:brightness-90 transition-all tooltip" data-tip="Eliminar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
        <div class="modal modal-bottom sm:modal-middle overflow-hidden">
            <div class="modal-box relative bg-white p-0 overflow-hidden border-t-4 w-72 sm:w-115">        
                <div class="p-6 pb-2 text-center">
                    <h3 class="text-xl font-bold flex items-center justify-center gap-2" style="color: #641332;">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Instrucciones de Importación
                    </h3>
                    <p class="text-sm text-gray-500 mt-2">
                        Asegúrate de que los nombres y el orden de la columnas dentro del archivo Excel coincidan con el siguiente formato:
                    </p>
                </div>
                <div class="px-6 py-3">
                    <div class="grid grid-cols-2 gap-3 bg-gray-50 p-4 rounded-xl border border-gray-100">
                        <div class="flex items-center gap-2 text-gray-700 font-medium">Nombre</div>
                        <div class="flex items-center gap-2 text-gray-700 font-medium">Correo</div>
                    </div>
                </div>
                <div class="p-6 pt-2">
                    <label for="modal-importar" 
                           onclick="document.getElementById('input-excel').click()" 
                           class="btn w-full border-none text-white hover:brightness-85 transition-all"
                           style="background-color: #641332;">
                           Seleccionar archivo
                    </label>
                <label for="modal-importar" class="btn btn-ghost btn-sm w-full mt-2 text-black font-normal hover:bg-white hover:brightness-90 transition-all">
                    Cancelar
                </label>
            </div>
        </div>
    </div>

    {{-- Input oculto --}}
    <input type="file" id="input-excel" wire:model="fileExcel" class="hidden" accept=".xlsx,.xls,.csv">
