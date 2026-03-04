@props([

    'noBack'=>null,

    'submit',
    'title' => null,
    'subtitle' => null,
    'subbutton' => null,
    'button',

    'modalID' => null,
])

@if($noBack === null)
    {{-- Contenedor principal del formulario (background) --}}
    <div class="flex justify-center items-center min-h-screen bg-gray-100 pt-17.5 pb-1">
@endif

    {{-- Tarjeta del formulario --}}
    <div class="card w-full max-w-lg bg-white shadow-xl p-7">

        {{-- Título del formulario (opcional) --}}
        @if($title)
            <h2 class="text-2xl font-bold mb-5 text-center text-imjuve">{{ $title }}</h2>
        @endif

        {{-- Subtítulo del formulario (opcional) --}}
        @if($subtitle)
            <p class="text-center text-gray-600 mb-5">{{ $subtitle }}</p>
        @endif

        {{-- Formulario --}}
        <form wire:submit.prevent="{{ $submit }}" class="space-y-4.5">
        @csrf
            
            {{-- Contenido del formumlario --}}
            {{ $slot }}

            @if($subbutton)
                {{-- Si se manda boton secundario (opcional), se usa despliegue de dos botones (pensado para modals) --}}
                <div class="modal-action pt-7">
                    <label for="{{ $modalID }}" wire:target="agregarEmpleado" wire:loading.remove class="btn btn-neutral btn-outline">{{ $subbutton }}</label>
                    <button wire:click="agregarEmpleado" wire:loading.remove class="btn btn-imjuve hover:brightness-85 text-white btn-ghost">{{ $button }}</button>
                </div>
            @else
                {{-- Si no hay botón secundario se usa despliegue de boton unico --}}
                <div class="text-center pt-7">
                    <button wire:loading.remove class="btn btn-imjuve w-full"
                        {{-- Si se manda un modalID se asume que el boton unico cerrrara ese modal --}}
                        @if($modalID) for="{{ $modalID }}" @else type="submit" @endif>
                        {{ $button }}
                    </button>
                </div>
            @endif
        </form>
    </div>

@if($noBack === null)
    </div>
@endif