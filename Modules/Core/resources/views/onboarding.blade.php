<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Completa tu acceso — IMJUVE Sistema de TI</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-surface font-sans antialiased min-h-screen flex items-center justify-center p-6">

<div class="w-full max-w-lg">

    <div class="flex items-center gap-3 mb-6 justify-center">
        <div class="w-10 h-10 rounded-lg overflow-hidden shrink-0" style="background:var(--color-gold)">
            <img src="/images/IMJCabezaT.png" alt="IMJUVE" class="w-full h-full object-contain p-1">
        </div>
        <div class="text-center">
            <p class="font-bold text-base leading-tight text-brand">IMJUVE - Sistemas</p>
            <p class="text-muted text-xs">Completa tu acceso para continuar</p>
        </div>
    </div>

    {{-- ── Pantalla 1: formulario ─────────────────────────────────────── --}}
    <div id="pantalla-formulario" class="bg-canvas border border-border rounded-2xl shadow-sm p-8">
        <h1 class="text-xl font-bold text-ink mb-1">Completa tu acceso</h1>
        <p class="text-sm text-muted mb-6">
            Antes de continuar, define tu contraseña y tu correo institucional.
            No vas a poder usar el sistema hasta terminar este paso.
        </p>

        <div id="errores" class="hidden mb-4 rounded-lg px-4 py-3 text-sm font-semibold"
             style="background:var(--color-error-container);color:var(--color-error)"></div>

        <form id="form-onboarding" class="space-y-5">
            <div>
                <label class="block text-sm font-medium text-ink mb-1.5">Correo institucional</label>
                <div class="flex items-stretch rounded-lg border border-border overflow-hidden focus-within:ring-2 focus-within:ring-brand">
                    <input type="text" id="correo_local" required autofocus
                           class="flex-1 px-4 py-2.5 text-sm outline-none bg-wash text-ink"
                           placeholder="nombre.apellido" pattern="[a-zA-Z0-9._-]+">
                    <span class="px-3 py-2.5 text-sm text-muted bg-surface-low select-none">@imjuventud.gob.mx</span>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1.5">Contraseña nueva</label>
                <input type="password" id="password" required minlength="8"
                       class="w-full rounded-lg px-4 py-2.5 text-sm border border-border bg-wash text-ink outline-none focus:ring-2 focus:ring-brand"
                       placeholder="Mínimo 8 caracteres">
            </div>

            <div>
                <label class="block text-sm font-medium text-ink mb-1.5">Confirmar contraseña</label>
                <input type="password" id="password_confirmation" required minlength="8"
                       class="w-full rounded-lg px-4 py-2.5 text-sm border border-border bg-wash text-ink outline-none focus:ring-2 focus:ring-brand">
            </div>

            <button type="submit" id="btn-enviar"
                    class="w-full py-2.5 rounded-lg text-sm font-bold text-white transition-opacity hover:opacity-90 active:scale-95 disabled:opacity-50"
                    style="background:var(--color-brand)">
                Guardar y continuar
            </button>
        </form>
    </div>

    {{-- ── Pantalla 2: código de recuperación ─────────────────────────── --}}
    <div id="pantalla-codigo" class="hidden bg-canvas border border-border rounded-2xl shadow-sm p-8">
        <div class="flex items-start gap-3 mb-5 rounded-lg px-4 py-3"
             style="background:var(--color-error-container);color:var(--color-error)">
            <span class="material-symbols-outlined shrink-0">warning</span>
            <p class="text-sm font-bold">
                Si cierras esta pantalla sin guardar tu código, NO hay forma de recuperar tu
                cuenta después. No vas a poder ver tus tickets ni recuperar el acceso — es
                la única copia que vas a tener.
            </p>
        </div>

        <div class="flex flex-col items-center gap-3 mb-5">
            <img id="qr-img" alt="Código QR de recuperación" class="w-56 h-56 border border-border rounded-lg">
            <p class="text-[11px] text-muted text-center max-w-xs">
                Escanea este código con tu celular para volver a entrar directo si olvidas tu
                contraseña. También queda guardado en el PDF que se acaba de descargar.
            </p>
        </div>

        <button type="button" id="btn-redescargar"
                class="w-full py-2.5 mb-3 rounded-lg text-sm font-bold border border-border text-ink hover:bg-wash transition-colors">
            Descargar PDF de nuevo
        </button>

        <button type="button" id="btn-continuar"
                class="w-full py-2.5 rounded-lg text-sm font-bold text-white transition-opacity hover:opacity-90 active:scale-95"
                style="background:var(--color-brand)">
            Ya guardé mi código, continuar
        </button>
    </div>
</div>

<script>
let pdfGenerado = null;

function mostrarErrores(errores) {
    const box = document.getElementById('errores');
    const mensajes = Object.values(errores).flat();
    box.textContent = mensajes.join(' ');
    box.classList.remove('hidden');
}

async function sha256Hex(texto) {
    const datos = new TextEncoder().encode(texto);
    const buffer = await crypto.subtle.digest('SHA-256', datos);
    return Array.from(new Uint8Array(buffer)).map(b => b.toString(16).padStart(2, '0')).join('');
}

function randomHex(bytes) {
    const arr = crypto.getRandomValues(new Uint8Array(bytes));
    return Array.from(arr).map(b => b.toString(16).padStart(2, '0')).join('');
}

function construirPdf(correo, codigo, qrDataUrl) {
    const doc = new window.jsPDF();

    doc.setFillColor(98, 17, 50); // guinda institucional
    doc.rect(0, 0, 210, 30, 'F');
    doc.setTextColor(255, 255, 255);
    doc.setFontSize(16);
    doc.text('IMJUVE — Código de recuperación de cuenta', 14, 18);

    doc.setTextColor(0, 0, 0);
    doc.setFontSize(11);
    doc.text(`Cuenta: ${correo}`, 14, 42);
    doc.text(`Generado: ${new Date().toLocaleString('es-MX')}`, 14, 49);

    doc.setFillColor(254, 226, 226);
    doc.rect(14, 58, 182, 28, 'F');
    doc.setTextColor(153, 27, 27);
    doc.setFont(undefined, 'bold');
    doc.setFontSize(11);
    doc.text('ADVERTENCIA: este código NO se puede recuperar si lo pierdes.', 18, 68, { maxWidth: 174 });
    doc.text('Es la única forma de acceder o cambiar tu contraseña si la olvidas.', 18, 76, { maxWidth: 174 });

    doc.addImage(qrDataUrl, 'PNG', 65, 95, 80, 80);

    doc.setTextColor(0, 0, 0);
    doc.setFont(undefined, 'normal');
    doc.setFontSize(9);
    doc.text('Código (respaldo si el QR no se puede escanear):', 14, 188);
    doc.setFont('courier', 'normal');
    doc.setFontSize(8);
    doc.text(codigo, 14, 195, { maxWidth: 182 });

    return doc;
}

document.getElementById('form-onboarding').addEventListener('submit', async function (e) {
    e.preventDefault();

    const correoLocal = document.getElementById('correo_local').value.trim();
    const password = document.getElementById('password').value;
    const passwordConfirmation = document.getElementById('password_confirmation').value;
    const correoCompleto = correoLocal + '@imjuventud.gob.mx';

    const btn = document.getElementById('btn-enviar');
    btn.disabled = true;
    btn.textContent = 'Procesando…';
    document.getElementById('errores').classList.add('hidden');

    if (password !== passwordConfirmation) {
        mostrarErrores({ password: ['Las contraseñas no coinciden.'] });
        btn.disabled = false;
        btn.textContent = 'Guardar y continuar';
        return;
    }

    const fecha = new Date().toISOString();
    const random = randomHex(32);
    const hash = await sha256Hex(correoCompleto + password + fecha + random);

    let resp, data;
    try {
        resp = await fetch('{{ route("perfil.completar") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                correo_local: correoLocal,
                password: password,
                password_confirmation: passwordConfirmation,
                hash: hash,
            }),
        });
        data = await resp.json();
    } catch (err) {
        mostrarErrores({ general: ['No se pudo conectar con el servidor. Intenta de nuevo.'] });
        btn.disabled = false;
        btn.textContent = 'Guardar y continuar';
        return;
    }

    if (!resp.ok) {
        mostrarErrores(data.errors ?? { general: ['Ocurrió un error inesperado.'] });
        btn.disabled = false;
        btn.textContent = 'Guardar y continuar';
        return;
    }

    // Éxito confirmado por el servidor — recién ahora se genera el QR/PDF.
    const url = `${location.origin}/recuperar/${hash}`;
    const qrDataUrl = await window.QRCode.toDataURL(url, { width: 320, margin: 2 });

    pdfGenerado = construirPdf(correoCompleto, hash, qrDataUrl);
    pdfGenerado.save('codigo-recuperacion-imjuve.pdf');

    document.getElementById('qr-img').src = qrDataUrl;
    document.getElementById('pantalla-formulario').classList.add('hidden');
    document.getElementById('pantalla-codigo').classList.remove('hidden');
});

document.getElementById('btn-redescargar').addEventListener('click', function () {
    if (pdfGenerado) pdfGenerado.save('codigo-recuperacion-imjuve.pdf');
});

document.getElementById('btn-continuar').addEventListener('click', function () {
    window.location.href = '{{ route("perfil") }}';
});
</script>
</body>
</html>
