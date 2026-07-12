<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión — IMJUVE Sistema de TI</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: var(--color-gold); border-radius: 10px; }
    </style>
    {{-- Detectar tema antes del primer paint para evitar flash --}}
    <script>
    (function () {
        const saved = localStorage.getItem('imj-theme');
        const sys   = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
        document.documentElement.dataset.theme = saved ?? sys;
    })();
    </script>
</head>
<body class="bg-surface font-sans antialiased min-h-screen flex">

    {{-- Panel izquierdo (marca) --}}
    <div class="hidden lg:flex lg:w-1/2 flex-col justify-between p-12"
         style="background-color:var(--color-brand);">

        {{-- Logo + nombre --}}
        <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-xl overflow-hidden shrink-0"
                 style="background:var(--color-gold)">
                <img src="/images/IMJCabezaT.png" alt="IMJUVE"
                     class="w-full h-full object-contain p-1">
            </div>
            <div>
                <p class="text-white font-bold text-lg leading-tight">IMJUVE - Sistemas</p>
                <p class="text-white/60 text-sm">Instituto Mexicano de la Juventud</p>
            </div>
        </div>

        {{-- Tagline --}}
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

    {{-- Panel derecho (formulario) --}}
    <div class="flex-1 flex items-center justify-center p-8 bg-canvas">
        <div class="w-full max-w-sm">

            {{-- Logo visible solo en móvil --}}
            <div class="flex items-center gap-3 mb-8 lg:hidden">
                <div class="w-10 h-10 rounded-lg overflow-hidden shrink-0"
                     style="background:var(--color-gold)">
                    <img src="/images/IMJCabezaT.png" alt="IMJUVE" class="w-full h-full object-contain p-1">
                </div>
                <div>
                    <p class="font-bold text-base leading-tight text-brand">IMJUVE - Sistemas</p>
                    <p class="text-muted text-xs">Instituto Mexicano de la Juventud</p>
                </div>
            </div>

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-ink">Iniciar sesión</h1>
                <p class="text-sm text-muted mt-1">Acceso para técnicos y administradores de TI</p>
            </div>

            @if($errors->any())
            <div class="mb-4 flex items-center gap-2 rounded-lg px-4 py-3 text-sm font-semibold"
                 style="background:var(--color-error-container);color:var(--color-error)">
                <span class="material-symbols-outlined text-sm">error</span>
                {{ $errors->first() }}
            </div>
            @endif

            <form method="POST" action="{{ route('login.post') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-ink mb-1.5">
                        Correo electrónico
                    </label>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus
                           class="w-full rounded-lg px-4 py-2.5 text-sm border outline-none transition-colors
                                  bg-wash border-border text-ink
                                  focus:ring-2 focus:ring-brand focus:border-brand"
                           placeholder="usuario@imjuventud.gob.mx">
                </div>

                <div>
                    <label class="block text-sm font-medium text-ink mb-1.5">
                        Contraseña
                    </label>
                    <input type="password" name="password" required
                           class="w-full rounded-lg px-4 py-2.5 text-sm border outline-none transition-colors
                                  bg-wash border-border text-ink
                                  focus:ring-2 focus:ring-brand focus:border-brand"
                           placeholder="••••••••">
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" name="remember" id="remember"
                           class="w-4 h-4 rounded accent-brand">
                    <label for="remember" class="text-sm text-muted cursor-pointer select-none">
                        Recordar sesión
                    </label>
                </div>

                <button type="submit"
                        class="w-full py-2.5 rounded-lg text-sm font-bold text-white transition-opacity hover:opacity-90 active:scale-95"
                        style="background:var(--color-brand)">
                    Iniciar sesión
                </button>
            </form>

            <div class="mt-6 pt-6 text-center" style="border-top:1px solid var(--color-border)">
                <p class="text-xs text-muted">
                    ¿Necesitas reportar un problema?
                    <a href="{{ url('/tickets/create') }}"
                       class="text-brand hover:underline font-semibold">
                        Crea un ticket aquí
                    </a>
                </p>
            </div>
        </div>
    </div>

</body>
</html>
