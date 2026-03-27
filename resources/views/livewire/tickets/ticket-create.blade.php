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

{{-- Botón y modal para información sobre tickets que se deben mandar a través de correo u oficio --}}
<div class="fixed bottom-10 right-32 z-[90]">
    <button type="button" 
            onclick="modalInfoTickets.showModal()"
            class="btn btn-circle shadow-2xl btn-imjuve text-white border-none w-18 h-18 tooltip" data-tip="¿Problemas para generar tu ticket?">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-12 h-12">
            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
        </svg>
    </button>
</div>

<x-form.modal id="modalInfoTickets"
    key="informacion_restricciones"
    title="Lineamientos para la gestión de solicitudes"
    subtitle="Con el objetivo de mantener un mejor control de las incidencias reportadas, así como en apego a las políticas en materia de seguridad de la información, si tu solicitud coincide con alguna situación mostrada a continuación, deberás enviarlo a través del medio correspondiente:"
    button="Cerrar"
    width="max-w-4xl">
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-2"> 
        {{-- Correo electrónico --}}
        <div class="bg-blue-50 border-l-4 border-blue-500 p-5 rounded-r-lg flex flex-col h-full">
            <div class="flex items-center gap-2 mb-3 text-blue-800">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                </svg>
                <h4 class="font-bold text-base leading-tight">Correo Electrónico</h4>
            </div>
            
            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1.5 ml-1 mb-4 flex-1">
                <li>Restablecimiento de contraseña para correo institucional</li>
                <li>Restablecimiento de contraseña para cuentas de usuario en equipos de cómputo</li>
                <li>Restablecimiento de contraseña para sistemas específicos</li>
            </ul>

            <div class="mt-auto bg-white/60 p-3 rounded-lg border border-blue-100 text-center">
                <p class="text-xs text-gray-800 leading-relaxed">
                    Deberá enviarse por el jefe superior inmediato a la cuenta <span class="font-bold text-blue-700">soporte@imjuventud.gob.mx</span>, con copia a <span class="font-bold text-blue-700">elara@imjuventud.gob.mx</span>.
                </p>
            </div>
            <div class="btn btn-sm w-full invisible pointer-events-none"></div>
        </div>

        {{-- Oficio --}}
        <div class="bg-emerald-50 border-l-4 border-emerald-500 p-5 rounded-r-lg flex flex-col h-full">
            <div class="flex items-center gap-2 mb-3 text-emerald-800">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-6 h-6 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                <h4 class="font-bold text-base leading-tight">Oficio Institucional</h4>
            </div>
                     
            <ul class="list-disc list-inside text-sm text-gray-700 space-y-1.5 ml-1 mb-4 flex-1">
                <li>Creación y eliminación de cuentas para correo institucional</li>
                <li>Creación y eliminación de cuentas de usuario en equipos de cómputo</li>
                <li>Creación y eliminación de cuentas para sistemas específicos</li>
            </ul>

            <div class="mt-auto space-y-3">
                <div class="bg-white/60 p-3 rounded-lg border border-emerald-100  text-center">
                    <p class="text-xs text-gray-800 leading-relaxed">
                        Deberá esta firmado por el <span class="font-bold">Director</span>, <span class="font-bold">Encargado(a) de la Dirección</span> o por el <span class="font-bold">Subdirector(a)</span> del área.
                    </p>
                </div>

                <a href="{{ asset('files/Solicitud de cuentas de correo y de usuario.docx') }}" 
                   download="Solicitud de cuentas de correo y de usuario.docx" 
                   class="btn btn-sm bg-gray-800 hover:bg-gray-900 text-white border-none normal-case w-full">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 mr-1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Descargar plantilla del formato
                </a>
            </div>
        </div>
    </div>
</x-form.modal>