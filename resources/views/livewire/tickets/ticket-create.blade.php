<x-form submit="createTicket" title="¿Cuál es tu problema?" button="Enviar ticket">

    {{-- Descripción --}}
    <fieldset class="fieldset relative">
        <legend class="fieldset-legend text-legend">Descripción</legend>
        <textarea wire:model="form.descripcion" class="textarea w-full @error('form.descripcion') border-red-500 border-3 @enderror"
                    placeholder="Describenos tu problema brevemente"></textarea>
        @error('form.descripcion') 
            <p class="absolute -bottom-4 right-0 font-bold text-red-500 ">{{ $message }}</p>
        @enderror
    </fieldset>

    {{-- Tipo de incidente --}}
    <x-form.select 
        legend="Tipo de incidente" 
        model="form.tipo" 
        placeholder="Selecciona el tipo de incidente" 
        :options="$tipos->pluck('nombre')"
    />

    {{-- Área --}}
    <x-form.select 
        legend="Área" 
        model="form.area" 
        placeholder="Selecciona el área en donde ocurrió el incidente" 
        :options="$areas->pluck('nombre')"
    />

    {{-- Nombre --}}
    <x-form.input
        legend="Nombre" 
        model="form.nombre" 
        type="text"
        placeholder="Nombre completo de quien reporta"
    />

    {{-- Correo --}}
    <x-form.input 
        legend="Correo" 
        model="form.correo"
        type="text"
        placeholder="Correo electrónico institucional de quien reporta"
    />

    <div wire:loading.class="opacity-100 visible" wire:target="createTicket" class="opacity-0 invisible transition-all duration-500 fixed inset-0 backdrop-blur-sm z-40 pointer-events-none"></div>
        <div wire:loading.class="opacity-100 visible" class="opacity-0 invisible transition-all duration-500 fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 bg-white border shadow-xl rounded-lg p-6 flex flex-col items-center gap-2 pointer-events-auto">
        <span class="loading loading-bars loading-xl text-primary"></span>
        <span class="font-semibold text-gray-700">Creando ticket</span>
    </div>

</x-form>