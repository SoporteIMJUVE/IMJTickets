# Íconos y Favicon — IMJUnificado

## Dónde están los assets institucionales

| Archivo | Ruta en el proyecto | Uso |
|---|---|---|
| Logo original PNG | `public/images/IMJCabezaT.png` | Imagen institucional con fondo transparente |
| Favicon multi-tamaño | `public/favicon.ico` | Ícono que aparece en la pestaña del navegador |

La imagen original proviene del sistema legado IMJTickets (`public/images/IMJCabezaT.png`). Si alguna vez se necesita regenerar el favicon, ver la sección **Cómo regenerar el favicon** más abajo.

---

## Cómo funciona el favicon

El favicon es el ícono pequeño que el navegador muestra en la pestaña, junto al título de la página.

```
[ 🏛 Reportar incidencia — IMJUVE ]  ← pestaña del navegador
```

Laravel lo sirve automáticamente desde `public/favicon.ico` — el navegador lo busca en `/favicon.ico` sin necesidad de configuración. Para que aparezca explícitamente en todas las páginas también se agrega una etiqueta `<link>` en cada `<head>`:

```html
<link rel="icon" href="/favicon.ico" type="image/x-icon">
```

Esta etiqueta está en dos lugares:

- `resources/views/components/layouts/app.blade.php` — cubre todas las páginas del CRM autenticado
- `Modules/Tickets/resources/views/create.blade.php` — formulario público (no usa el layout del CRM)

El resto de las páginas hereda el favicon a través del layout `app.blade.php`.

---

## Por qué `.ico` y no `.png`

| Formato | Problema |
|---|---|
| `.png` | Un solo tamaño. El navegador lo escala y se ve borroso en pantallas de alta densidad (Retina, 4K). |
| `.svg` | No soportado como favicon en Safari ni en versiones antiguas de Chrome. |
| `.ico` | Contiene múltiples tamaños en un solo archivo (16×16, 32×32, 48×48). El navegador elige el que mejor se ajusta a la pantalla y densidad de píxeles. |

El `.ico` es el único formato que garantiza que el ícono se vea nítido en todos los navegadores y sistemas operativos sin trabajo adicional.

---

## Cómo regenerar el favicon

Si en algún momento se actualiza el logo institucional, regenerar el favicon con ImageMagick (ya instalado en el sistema de desarrollo):

```bash
# Desde la raíz del proyecto
magick public/images/IMJCabezaT.png \
  -resize 48x48 \( +clone -resize 32x32 \) \( +clone -resize 16x16 \) \
  public/favicon.ico
```

Esto genera un `.ico` con los tres tamaños estándar (48, 32, 16 px) a partir del PNG original.

Después de regenerar, los usuarios deben hacer `Ctrl + Shift + R` en el navegador para forzar la recarga del favicon desde caché.

---

## Nota sobre caché de favicons

Los navegadores cachean el favicon de forma agresiva — pueden mostrarlo durante días aunque hayas cambiado el archivo. Para forzar la actualización:

- **Chrome / Edge:** `Ctrl + Shift + R`
- **Firefox:** `Ctrl + Shift + R` o limpiar caché desde Configuración
- **Incógnito:** siempre muestra el favicon sin caché
