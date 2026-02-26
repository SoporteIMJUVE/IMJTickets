<!DOCTYPE html>
<html lang="es">

<head>
    {{-- Meta etiquetas --}}
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="{{ asset('images/IMJCabezaT.png') }}">
    <title>IMJTickets</title>

    {{-- Vite para Tailwind CSS y DaisyUI --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- Livewire --}}
    @livewireStyles

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