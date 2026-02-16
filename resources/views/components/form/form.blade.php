@props([
    'submit',
    'title' => null,
    'button'
])

{{-- Contenedor principal del formulario (background) --}}
<div class="flex justify-center items-center min-h-screen bg-gray-100 pt-17.5 pb-1">

    {{-- Tarjeta del formulario --}}
    <div class="card w-full max-w-lg bg-white shadow-xl p-7">

        {{-- Título del formulario (opcional) --}}
        @if($title)
            <h2 class="text-2xl font-bold mb-5 text-center text-imjuve">{{ $title }}</h2>
        @endif

        {{-- Formulario --}}
        <form wire:submit.prevent="{{ $submit }}" class="space-y-4.5">
        @csrf
            
            {{ $slot }}

            {{-- Botón --}}
            <div class="text-center pt-7">
                <button wire:loading.remove type="submit" class="btn btn-imjuve w-full">{{ $button }}</button>
            </div>
        </form>
    </div>
</div>