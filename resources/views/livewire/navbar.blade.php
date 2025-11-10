<div class="navbar fixed top-0 left-0 w-full flex-col items-center justify-center p-0 z-71">

    {{-- Parte superior de la barra (botones) --}}
    <div class="flex w-full bg-white shadow-md px-20 py-3 z-70">

        {{-- Versión --}}
        <span class="absolute left-5 translate-y-3 text-xs text-gray-300">
            v0.11.1
        </span>
        
        {{-- Logo --}}
        <div class="flex-1">
            <a class="btn btn-ghost p-0 h-auto w-auto"
                href="{{ route('bienvenida') }}"
                wire:navigate.hover>
                <img src="{{ asset('images/IMJLogo.jpeg') }}" alt="IMJTickets" class="h-10">
            </a>
        </div>

        {{-- Botones centrales --}}
        @if($this->showCentralNav)

            {{-- Contenedor absoluto centrado para los botones de Admin y Usuario --}}
            <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2">

                {{--
                <button class="btn btn-imjuve">
                    Buscar
                </button>
                --}}
                {{-- Botón para exportar tickets --}}
                <dropdown-export />
                <x-form.dropdown-export />
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

                    <button wire:click="logout" class="btn btn-imjuve">
                        Cerrar sesión
                    </button>
                    
                    <div class="absolute left-full pl-3 top-1/2 -translate-y-1/2 ml-2">
                        <button class="btn btn-circle btn-ghost text-gray-500 hover:bg-transparent transition">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                        </button>
                    </div>

                </div>
            @endauth

        </div>

    </div>

    
    {{-- Parte inferior de la barra (alertas) --}}

    {{-- Alerta de éxito --}}
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

</div>
