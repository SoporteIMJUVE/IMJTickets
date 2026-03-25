<div>
    @if( $role == "user" )
        <x-form submit="userLogin" button="Revisa tus tickets" wire:loading.remove>
            {{-- Correo --}}
            <x-form.input
                legend="Ingresa tu correo institucional" 
                model="uForm.correo"
                type="text"
            />
            {{-- Loading inicio sesión empleado --}}
            <x-loading target="userLogin" message="Validando correo"/>
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
            {{-- Loading inicio sesión admin --}}
            <x-loading target="adminLogin" message="Validando credenciales"/>

        </x-form>
    @endif
</div>