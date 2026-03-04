@props([
    'id',
    'submit',
    'title' => null,
    'subtitle' => null,
    'button',
    'subbutton' => null
])

<dialog id="{{ $id }}" class="modal">
    <div class="modal-box max-w-lg bg-transparent shadow-none border-0 p-0 w-full flex justify-center">
            <x-form noBack="noBack"
                    submit="{{ $submit }}"
                    title="{{ $title }}"
                    subtitle="{{ $subtitle }}" 
                    subbutton="{{ $subbutton }}"
                    button="{{ $button }}"
                    modalID="{{ $id }}">

                {{-- Contenido del modal --}}
                {{ $slot }}

            </x-form>
    </div>

    {{-- Modal backdrop (capa de fondo que cierra el modal al hacer click) --}}
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
        
</dialog>

