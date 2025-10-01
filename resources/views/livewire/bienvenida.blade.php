<x-layouts.app>
    <div class="flex items-center justify-center bg-gray-100 h-screen">
        <div class="text-center space-y-20">

            <h1 class="text-7xl font-bold text-imjuve">Bienvenido a IMJTickets</h1>

            <div class="flex max-w-md mx-auto flex-col">
                <div class="card rounded-box grid h-20 place-items-center">
                    <a class="btn btn-imjuve btn-xl w-full"
                       href="{{ route('tickets.create') }}"
                       wire:navigate.hover>
                        Crea un nuevo ticket
                    </a>
                </div>
                <div class="divider divider-neutral text-black">O</div>
                <div class="card rounded-box grid h-20 place-items-center">
                    <a class="btn btn-imjuve btn-xl w-full"
                       href="{{ route("login", ['role' => 'user'] ) }}"
                       wire:navigate.hover>
                        Revisa tus tickets
                    </a>
                </div>
            </div>

        </div>

    </div>
</x-layouts.app>