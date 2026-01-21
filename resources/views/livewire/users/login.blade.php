<div>
    @if( $role == "user" )
        <x-form submit="userLogin" button="Revisa tus tickets" wire:loading.remove>
            {{-- Correo --}}
            <x-form.input
                legend="Ingresa tu correo institucional" 
                model="uForm.correo"
                type="text"
            />
            <div wire:loading.class="opacity-100 visible" wire:target="userLogin" class="opacity-0 invisible transition-all duration-500 fixed inset-0 backdrop-blur-sm z-40 pointer-events-none"></div>
            <div wire:loading.class="opacity-100 visible" wire:target="userLogin" class="opacity-0 invisible transition-all duration-500 fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 bg-white border shadow-xl rounded-lg p-6 flex flex-col items-center gap-2 pointer-events-auto">
                <span class="loading loading-bars loading-xl text-primary"></span>
                <span class="font-semibold text-gray-700">Validando correo</span>
            </div>
        </x-form>
    @elseif( $role == "admin" )
        <x-form submit="adminLogin" button="Iniciar sesion" wire:loading.remove>

            {{-- Correo --}}
            <x-form.input
                legend="Ingresa tu correo institucional" 
                model="aForm.correo"
                type="text"
            />

            {{-- Cortraseña --}}
            <x-form.input 
                legend="Ingresa tu contraseña" 
                model="aForm.contrasenia"
                type="password"
            />
            <div wire:loading.class="opacity-100 visible" wire:target="adminLogin" class="opacity-0 invisible transition-all duration-500 fixed inset-0 backdrop-blur-sm z-40 pointer-events-none"></div>
            <div wire:loading.class="opacity-100 visible" wire:target="adminLogin" class="opacity-0 invisible transition-all duration-500 fixed top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 z-50 bg-white border shadow-xl rounded-lg p-6 flex flex-col items-center gap-2 pointer-events-auto">
                <span class="loading loading-bars loading-xl text-primary"></span>
                <span class="font-semibold text-gray-700">Validando credenciales</span>
            </div>

        </x-form>
    @endif
</div>