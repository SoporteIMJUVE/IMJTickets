<!DOCTYPE html>
<html lang="es">

<head>
    {{-- Meta etiquetas --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/IMJCabezaT.png') }}">
    <title>IMJTickets</title>

    {{-- Alpine.js --}}
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    {{-- Vite para Tailwind CSS y DaisyUI --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Livewire --}}
    @livewireStyles
    @livewireScripts

    {{-- SweetAlert --}}
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
    {{-- Barra de navegación --}}
    <livewire:navbar />

    {{-- Contenido principal --}}
    <div>
        {{ $slot }}
    </div>

    {{-- Livewire --}}
    @livewireScripts
</body>
</html>