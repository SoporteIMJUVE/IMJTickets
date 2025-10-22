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

</x-form>