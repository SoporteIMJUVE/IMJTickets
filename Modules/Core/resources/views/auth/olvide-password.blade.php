<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar acceso — IMJUVE Sistema de TI</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface font-sans antialiased min-h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-sm bg-canvas border border-border rounded-2xl shadow-sm p-8">
        <h1 class="text-lg font-bold text-ink mb-1">Recuperar acceso</h1>
        <p class="text-sm text-muted mb-6">
            Escanea el código QR de tu PDF con tu celular para entrar directo,
            o pega aquí el código en texto que viene como respaldo en ese mismo PDF.
        </p>

        <div id="error-codigo" class="hidden mb-4 rounded-lg px-4 py-3 text-sm font-semibold"
             style="background:var(--color-error-container);color:var(--color-error)">
            Ese código no es válido. Revisa que lo copiaste completo, sin espacios.
        </div>

        <form id="form-codigo" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-ink mb-1.5">Código de recuperación</label>
                <input type="text" id="codigo" required autofocus
                       class="w-full rounded-lg px-4 py-2.5 text-sm border border-border bg-wash text-ink outline-none focus:ring-2 focus:ring-brand font-mono"
                       placeholder="64 caracteres, ej. a1b2c3...">
            </div>
            <button type="submit"
                    class="w-full py-2.5 rounded-lg text-sm font-bold text-white transition-opacity hover:opacity-90 active:scale-95"
                    style="background:var(--color-brand)">
                Entrar con mi código
            </button>
        </form>

        <div class="mt-6 pt-6 text-center" style="border-top:1px solid var(--color-border)">
            <a href="{{ route('login') }}" class="text-xs text-brand hover:underline font-semibold">
                Volver al login
            </a>
        </div>
    </div>

    <script>
    document.getElementById('form-codigo').addEventListener('submit', function (e) {
        e.preventDefault();
        const codigo = document.getElementById('codigo').value.trim();
        if (!/^[a-f0-9]{64}$/i.test(codigo)) {
            document.getElementById('error-codigo').classList.remove('hidden');
            return;
        }
        window.location.href = '{{ url('/recuperar') }}/' + codigo;
    });
    </script>
</body>
</html>
