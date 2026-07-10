<!DOCTYPE html>
<html lang="es" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión — IMJUVE Sistema de TI</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 font-sans antialiased">

<div class="min-h-screen flex">

    {{-- Left panel (maroon brand) --}}
    <div class="hidden lg:flex lg:w-1/2 flex-col justify-between p-12"
         style="background-color:var(--color-brand);">
        <div>
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-canvas/20 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-white" fill="none"
                         viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0H3"/>
                    </svg>
                </div>
                <div>
                    <p class="text-white font-bold text-lg leading-tight">IMJUVE</p>
                    <p class="text-white/60 text-sm">Sistema de TI</p>
                </div>
            </div>
        </div>

        <div>
            <h2 class="text-4xl font-bold text-white leading-snug">
                Gestión integral<br>
                <span style="color:var(--color-gold);">de activos</span><br>
                institucionales
            </h2>
            <p class="text-white/60 mt-4 text-sm leading-relaxed max-w-sm">
                Control de usuarios, equipos de cómputo, red, teléfonos, impresoras e insumos — todo en un solo sistema.
            </p>
        </div>

        <p class="text-white/30 text-xs">Instituto Mexicano de la Juventud · Subdirección de Sistemas</p>
    </div>

    {{-- Right panel (login form) --}}
    <div class="flex-1 flex items-center justify-center p-8">
        <div class="w-full max-w-sm">

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900">Iniciar sesión</h1>
                <p class="text-sm text-gray-500 mt-1">Acceso para técnicos y administradores de TI</p>
            </div>

            @if($errors->any())
                <div class="alert alert-error mb-4 text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Correo electrónico
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="input input-bordered w-full bg-canvas focus:ring-2 focus:ring-imjuve/30"
                           placeholder="usuario@imjuventud.gob.mx">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">
                        Contraseña
                    </label>
                    <input type="password" name="password" required
                           class="input input-bordered w-full bg-canvas focus:ring-2 focus:ring-imjuve/30"
                           placeholder="••••••••">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="remember" id="remember" class="checkbox checkbox-sm mr-2">
                    <label for="remember" class="text-sm text-gray-600">Recordar sesión</label>
                </div>

                <button type="submit" class="btn btn-imjuve w-full">
                    Iniciar sesión
                </button>
            </form>

            <div class="mt-6 pt-6 border-t border-gray-100 text-center">
                <p class="text-xs text-gray-400">
                    ¿Necesitas reportar un problema?
                    <a href="{{ url('/tickets/create') }}" class="text-imjuve hover:underline font-medium">
                        Crea un ticket aquí
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

</body>
</html>
