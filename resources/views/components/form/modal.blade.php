@props([
    'id',
    'key',
    'submit' => null,
    'title' => null,
    'subtitle' => null,
    'button',
    'subbutton' => null,
    'target' => null,
    'message' => null
])

<dialog id="{{ $id }}" wire:key="{{ $id }}{{ $key }}" class="modal" {{ $attributes }}>
    
    @if($target)
        <div wire:loading.delay.class="opacity-100 visible" 
             wire:target="{{ $target }}" 
             class="opacity-0 invisible transition-all duration-500 fixed inset-0 bg-black/30 backdrop-blur-sm z-[100] pointer-events-auto"></div>
        
        <div wire:loading.delay.class="opacity-100 visible" 
             wire:target="{{ $target }}" 
             class="opacity-0 invisible transition-all duration-500 fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-[110] bg-white border border-gray-100 shadow-2xl rounded-2xl p-7 flex flex-col items-center gap-3">
            <span class="loading loading-dots loading-lg text-imjuve"></span>
            <span class="font-semibold text-black animate-pulse">{{ $message }}</span>
        </div>
    @endif

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