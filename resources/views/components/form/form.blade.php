@props([

    'noBack'=>false,

    'submit' => null,
    'title' => null,
    'subtitle' => null,
    'subbutton' => null,
    'button',

    'modalId' => null,
])

@unless($noBack)
    {{-- Contenedor principal del formulario (background) --}}
    <div class="flex justify-center items-center min-h-screen bg-gray-100 pt-17.5 pb-1">
@endunless

    {{-- Tarjeta del formulario --}}
    <div class="card w-full max-w-lg bg-white shadow-xl p-7">

        {{-- Título del formulario (opcional) --}}
        @if($title)
            <h2 class="text-2xl font-bold mb-5 text-center text-imjuve">{{ $title }}</h2>
        @endif

        {{-- Subtítulo del formulario (opcional) --}}
        @if($subtitle)
            <p class="text-center text-gray-800 mb-5">{{ $subtitle }}</p>
        @endif

        {{-- Formulario --}}
        <form wire:submit.prevent="{{ $submit }}" class="space-y-4.5">
        @csrf
            
            {{-- Contenido del formumlario --}}
            {{ $slot }}

            @if($subbutton)
                {{-- Si se manda boton secundario (opcional) se usa despliegue de dos botones (pensado para modals),
                    el subbutton siempre cierra el modal, el button principal ejecuta el submit y luego cierra el modal (probar si no se rompe el form si no hay modal asocado) --}}
                <div class="modal-action pt-7">
                    <button class="btn btn-soft"
                            type="button"
                            onclick="document.getElementById('{{ $modalId }}').close()">
                        {{ $subbutton }}
                    </button>
                    <button class="btn btn-imjuve"
                            type="submit">
                        {{ $button }}
                    </button>
                </div>
            @else
                {{-- Si no hay botón secundario se usa despliegue de boton unico --}}
                <div class="text-center pt-7">
                    <button class="btn btn-imjuve w-full"
                        {{-- Si se manda un modalId se asume que el boton unico cerrara ese modal --}}
                        @if($modalId)
                            onclick="document.getElementById('{{ $modalId }}').close()"
                        @else
                            type="submit"
                        @endif
                    >
                        {{ $button }}
                    </button>
                </div>
            @endif
        </form>
    </div>

@if($noBack === null)
    </div>
@endif