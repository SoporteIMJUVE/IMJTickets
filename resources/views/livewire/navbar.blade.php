<div class="navbar fixed top-0 left-0 w-full flex-col items-center justify-center p-0 z-71">

    {{-- Parte superior de la barra (botones) --}}
    <div class="flex w-full bg-white shadow-md px-20 py-3 z-70">

        {{-- Versión --}}
        <span class="absolute left-5 translate-y-3 text-xs text-gray-300">
            v1.0.1
        </span>
        
        {{-- Logo --}}
        <div class="flex-1">
            <a class="btn btn-ghost p-0 h-auto w-auto tooltip tooltip-bottom" data-tip="Ir a la página de inicio"
                href="{{ route('bienvenida') }}"
                wire:navigate.hover>
                <img src="{{ asset('images/IMJLogo.jpeg') }}" alt="IMJTickets" class="h-10">
            </a>
        </div>

        {{-- Botones centrales --}}
        @if($this->showCentralNav)

            {{-- Contenedor absoluto centrado--}}
            <div class="absolute left-1/2 -translate-x-1/2 flex items-center gap-3">

                <label class="input w-100">
                    <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <g
                        stroke-linejoin="round"
                        stroke-linecap="round"
                        stroke-width="2.5"
                        fill="none"
                        stroke="currentColor"
                        >
                        <circle cx="11" cy="11" r="8"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                        </g>
                    </svg>
                    <input type="search" class="grow" placeholder="Buscar por palabras clave"
                            wire:model.live="wordSearch"/>
                </label>

                @if(request()->routeIs('tickets.*'))
                    {{-- Botón exportar --}}
                    <x-form.dropdown-export />
                
                @elseif(request()->routeIs('admin.validar-correos'))
                    {{-- Botón Importar Excel --}}
                    <label for="modal-importar" class="btn btn-sm btn-outline btn-success gap-2 h-10 cursor-pointer">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        <span class="hidden xl:inline">Importar Excel</span>
                    </label>

                    {{-- Botón Agregar Empleado --}}
                    <button class="btn btn-imjuve hover:brightness-85 text-white gap-2 border-none h-10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                        <span class="hidden xl:inline">Agregar empleado</span>
                    </button>
                    
                @endif

            </div>
        @endif

        <div class="flex-none gap-2">

            @guest
                <a class="btn btn-imjuve"
                    href="{{ route("login", ['role' => 'admin'] ) }}"
                    wire:navigate.hover>
                    Administración
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </a>
            @endguest
    
            @auth
                <!-- contenedor relativo inline-block para logout + ghost -->
                <div class="relative inline-block">                    
                    <button wire:click="logout" class="btn btn-imjuve hover:brightness-85">
                        Cerrar sesión
                    </button>

                    <div x-data="{ drawerOpen: false }" 
                         class="absolute left-full pl-3 top-1/2 -translate-y-1/2 ml-2">
                        <button @click="drawerOpen = !drawerOpen" 
                                class="btn btn-circle btn-ghost text-gray-500 hover:bg-transparent transition cursor-pointer tooltip tooltip-bottom" 
                                :class="{ 'bg-gray-200 shadow-inner scale-95': drawerOpen }" 
                                data-tip="Ajustes">
                            <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="size-6">
                                <path d="M4 6.75H20" stroke="currentColor" stroke-width="2.0" stroke-linecap="round"/>
                                <path d="M4 12.75H20" stroke="currentColor" stroke-width="2.0" stroke-linecap="round"/>
                                <path d="M4 18.75H20" stroke="currentColor" stroke-width="2.0" stroke-linecap="round"/>
                            </svg>
                        </button>
                        
                        <template x-teleport="body">
                            <div>
                                {{-- Drawer Sidebar --}}
                                <div class="fixed top-16 right-0 h-auto w-90 bg-white shadow-[-8px_0_15px_-3px_rgba(0,0,0,0.1)] rounded-bl-lg z-60" 
                                    style="display: none;" 
                                    x-show="drawerOpen"
                                    @click.outside="drawerOpen = false"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="translate-x-full opacity-0"
                                    x-transition:enter-end="translate-x-0 opacity-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="translate-x-0 opacity-100"
                                    x-transition:leave-end="translate-x-full opacity-0">
                            
                                    <div class="py-2 px-4 min-w-max">
                                        {{-- Opciones del menú --}}
                                        <a href="{{ route('admin.validar-correos') }}" 
                                           wire:navigate.hover 
                                           class="block w-full text-left px-4 py-3 hover:bg-blue-600 hover:text-white transition rounded-sm text-gray-800 text-sm cursor-pointer">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="inline-block w-4 h-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            Validar correos
                                        </a>

                                        <button class="w-full text-left px-4 py-3 hover:bg-blue-600 hover:text-white transition rounded-sm text-gray-800 text-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="inline-block w-4 h-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7m0 0c0 2.21-3.582 4-8 4s-8-1.79-8-4m0 0C4 4.79 7.582 3 12 3s8 1.79 8 4" />
                                            </svg>
                                            Gestionar BD
                                        </button>

                                        <div class="border-t border-gray-200 my-1"></div>
                                
                                        <button class="w-full text-left px-4 py-3 hover:bg-emerald-600 hover:text-white transition rounded-sm text-gray-800 text-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="inline-block w-4 h-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                            </svg>
                                                Editar campos
                                        </button>

                                        <div class="border-t border-gray-200 my-1"></div>
                                
                                        <button class="w-full text-left px-4 py-3 hover:bg-red-700 hover:text-white transition rounded-sm text-gray-800 text-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="inline-block w-4 h-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                            </svg>
                                            Gestionar administradores
                                        </button>

                                        <button class="w-full text-left px-4 py-3 hover:bg-red-700 hover:text-white transition rounded-sm text-gray-800 text-sm">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="inline-block w-4 h-4 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Cuenta
                                        </button>
                                    </div>
                                </div>

                                {{-- Overlay para cerrar --}}
                                <label x-show="drawerOpen" 
                                       @click="drawerOpen = false"
                                       class="fixed inset-0 z-50 bg-black/15"
                                       style="display: none;"
                                       x-transition:enter="transition ease-out duration-200"
                                       x-transition:enter-start="opacity-0"
                                       x-transition:enter-end="opacity-100"
                                       x-transition:leave="transition ease-in duration-150"
                                       x-transition:leave-start="opacity-100"
                                       x-transition:leave-end="opacity-0">
                                </label>
                            </div>
                        </template>
                    </div>
                </div>
            @endauth
        </div>
    </div>

    {{-- Parte inferior de la barra (alertas) --}}

    {{-- Alerta de ticket creado --}}
    @if (session()->has('createTicket'))

        <!-- Separa el string de la sesion en los mensajes de la alerta-->
        {{-- @php
            [$m1, $m2] = explode('|', session('createTicket'));
        @endphp --}}

        <div class="w-full bg-transparent px-20 z-69">
            <div role="alert" class="alert alert-success shadow-md -mt-3 pt-5 z-70 cursor-pointer"
                x-data="{ show: true }" x-show="show" @click="show = false"
                x-transition:leave="transition transform duration-500" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-10">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 shrink-0 stroke-current" fill="none" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>{{ session('createTicket') }}</span>
            </div>
        </div>

    @endif

    {{-- Alerta de ticket recibido --}}
    @auth
        <div {{-- wire:poll="checkNew" --}} class="w-full bg-transparent px-20 z-69">

            {{-- Sonido de la alerta --}}
            <audio id="alert-sound" src="{{ asset('sounds/new-notification-022-370046.mp3') }}" preload="auto"></audio>
            <script>
                document.addEventListener('livewire:init', () => {
                    Livewire.on('newTicketSound', () => {
                        const audio = document.getElementById('alert-sound');
                        audio.currentTime = 0;
                        audio.play().catch(() => {
                            console.warn('Audio bloqueado por el navegador. Se requiere interacción del usuario para reproducir sonidos.');
                        });
                    });
                });
            </script>

            {{-- Visual de la alerta --}}
            @if (auth()->user()->new_ticket_alert)
                <div role="alert" class="alert alert-warning shadow-md -mt-3 pt-5 z-70 cursor-pointer"
                    wire:click="clearNew(true)"
                    wire:transition.origin.top.duration.750ms>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    <span>¡Nuevo ticket recibido!</span>
                    <button type="button" class="btn btn-ghost btn-sm btn-circle ml-auto"
                            wire:click.stop="clearNew(false)">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            @endif
        </div>
    @endauth

    {{-- Loading exportación --}}
    <div wire:loading.class="opacity-100 visible" wire:target="emitExcel, emitPdf" class="opacity-0 invisible transition-all duration-500 fixed inset-0 backdrop-blur-sm z-40 pointer-events-none"></div>
    <div wire:loading.class="opacity-100 visible" wire:target="emitExcel, emitPdf" class="opacity-0 invisible transition-all duration-500 fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 bg-white border shadow-xl rounded-lg p-6 flex flex-col items-center gap-2 pointer-events-auto">
        <span class="loading loading-bars loading-xl text-primary"></span>
        <span class="font-semibold text-gray-700">Generando archivo</span>
    </div>

    <div wire:loading.class="opacity-100 visible" wire:target="logout" class="opacity-0 invisible transition-all duration-500 fixed inset-0 backdrop-blur-sm z-40 pointer-events-none"></div>
    <div wire:loading.class="opacity-100 visible" wire:target="logout" class="opacity-0 invisible transition-all duration-500 fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 bg-white border shadow-xl rounded-lg p-6 flex flex-col items-center gap-2 pointer-events-auto">
        <span class="loading loading-bars loading-xl text-primary"></span>
        <span class="font-semibold text-gray-700">Cerrando sesión</span>
    </div>
</div>
