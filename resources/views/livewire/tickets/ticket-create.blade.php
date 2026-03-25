<x-form submit="createTicket" title="¿Cuál es tu problema?" button="Enviar ticket">

    {{-- Descripción --}}
    <x-form.textarea 
        legend="Descripción" 
        model="form.descripcion"
        placeholder="Describenos tu problema brevemente"
    />

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

    {{-- Loading cerrar sesión --}}
    <x-loading target="createTicket" message="Creando ticket"/>

</x-form>