<div class="navbar fixed top-0 left-0 w-full flex-col items-center justify-center p-0 z-50">

    {{-- Parte superior de la barra (botones) --}}
    <div class="flex w-full bg-white shadow-md px-20 py-3 z-10">

        {{-- Versión --}}
        <span class="absolute left-7 top-1/2 -translate-y-1/2 text-xs text-gray-300">
            v0.8.0
        </span>
        
        <div class="flex-1">
            <a class="btn btn-ghost p-0 h-auto w-auto"
                href="{{ route('bienvenida') }}"
                wire:navigate.hover>
                <img src="{{ asset('images/IMJLogo.jpeg') }}" alt="IMJTickets" class="h-10">
            </a>
        </div>

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
                <button wire:click="logout" class="btn btn-imjuve">
                    Cerrar sesión
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </button>
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

        <div class="w-full bg-transparent px-20 z-9">
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
