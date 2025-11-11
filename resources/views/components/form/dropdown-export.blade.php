@auth
    <div class="dropdown">

        <button tabindex="0" role="button" class="btn btn-imjuve">
            <span>Exportar</span>
        </button>

        <ul tabindex="0" class="dropdown-content absolute left-1/2 top-full mt-2 -translate-x-1/2 p-2 shadow bg-base-100 rounded-box w-72 z-[1]">
            <li>
                <div class="grid gap-2">
                    <button wire:click.prevent="emitExcel"
                        class="flex items-start gap-3 p-3 rounded-lg transition-colors duration-150 hover:bg-gray-200 dark:hover:bg-gray-800 cursor-pointer">
                        <svg class="w-5 h-5 text-green-600 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M3 3h18v18H3V3z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8 7l4 5-4 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div class="text-left">
                            <div class="font-medium">Excel (.xlsx)</div>
                            <div class="text-xs text-gray-500">Descargar hoja de cálculo</div>
                        </div>
                    </button>

                    <button wire:click.prevent="emitPdf"
                        class="flex items-start gap-3 p-3 rounded-lg transition-colors duration-150 hover:bg-gray-200 dark:hover:bg-gray-800 cursor-pointer">
                        <svg class="w-5 h-5 text-red-600 shrink-0" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6 2h7l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V3a1 1 0 0 1 1-1z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M13 2v6h6" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <div class="text-left">
                            <div class="font-medium">PDF (.pdf)</div>
                            <div class="text-xs text-gray-500">Descargar documento imprimible</div>
                        </div>
                    </button>
                </div>
            </li>
        </ul>
    </div>
@endauth