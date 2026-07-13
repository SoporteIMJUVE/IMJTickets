<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Código inválido — IMJUVE Sistema de TI</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface font-sans antialiased min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-sm bg-canvas border border-border rounded-2xl shadow-sm p-8 text-center">
        <span class="material-symbols-outlined text-5xl block mb-3" style="color:var(--color-error)">error</span>
        <h1 class="text-lg font-bold text-ink mb-2">Código inválido</h1>
        <p class="text-sm text-muted mb-6">
            Este enlace de recuperación no corresponde a ninguna cuenta activa.
            Revisa que copiaste el código completo, o contacta a Sistemas si el
            problema continúa.
        </p>
        <a href="{{ route('login') }}"
           class="inline-block px-4 py-2.5 rounded-lg text-sm font-bold text-white transition-opacity hover:opacity-90"
           style="background:var(--color-brand)">
            Ir al login
        </a>
    </div>
</body>
</html>
