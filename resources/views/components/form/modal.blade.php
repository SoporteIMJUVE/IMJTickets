@props([
    'id',
    'key',
    'submit' => null,
    'title' => null,
    'subtitle' => null,
    'button',
    'subbutton' => null
])

<dialog id="{{ $id }}" wire:key="{{ $id }}{{ $key }}" class="modal">
    <div class="modal-box max-w-lg bg-transparent shadow-none border-0 p-0 w-full flex justify-center">
        <x-form noBack="true"
                submit="{{ $submit }}"
                title="{{ $title }}"
                subtitle="{{ $subtitle }}" 
                subbutton="{{ $subbutton }}"
                button="{{ $button }}"
                modalId="{{ $id }}">

            {{-- Contenido del modal --}}
            {{ $slot }}

        </x-form>
    </div>

    {{-- Modal backdrop (capa de fondo que cierra el modal al hacer click) --}}
    <form method="dialog" class="modal-backdrop w-full h-full">
        <button>close</button>
    </form>

</dialog>