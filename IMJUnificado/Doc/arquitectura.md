# Arquitectura Modular

---

## Estructura de carpetas

```
IMJUnificado/
│
├── Modules/                        ← Todo el código de negocio vive aquí
│   ├── Core/                       ← Base del sistema (auth, layout, dashboard)
│   ├── CRM/                        ← Directorio de empleados
│   ├── Tickets/                    ← Sistema de soporte
│   ├── Kardex/                     ← Inventario de equipos e insumos
│   ├── Network/                    ← Gestión de red e IPs
│   ├── Telefonos/                  ← Extensiones telefónicas (stub)
│   ├── Impresoras/                 ← Inventario de impresoras (stub)
│   └── Mantenimiento/              ← Reportes de mantenimiento (stub)
│
├── app/                            ← Solo lo que es truly global
│   ├── Http/Controllers/           ← Controlador base
│   ├── Models/User.php             ← Único modelo en app/ (autenticación)
│   └── Providers/AppServiceProvider.php
│
├── resources/
│   └── views/
│       ├── components/layouts/app.blade.php   ← Layout compartido (sidebar + topbar)
│       └── layouts/app.blade.php              ← Alias del anterior
│
├── database/
│   ├── migrations/                 ← Migraciones globales + las de cada módulo
│   └── seeders/                    ← Seeders con datos reales de Excel
│
├── routes/
│   └── web.php                     ← Solo redirige a los módulos; casi vacío
│
└── .env                            ← Configuración local (nunca en git)
```

### Anatomía de un módulo

Cada módulo en `Modules/NombreModulo/` tiene exactamente esta estructura:

```
Modules/CRM/
├── app/
│   ├── Http/Controllers/CRMController.php    ← Lógica del módulo
│   └── Providers/
│       ├── CRMServiceProvider.php            ← Registra el módulo en Laravel
│       └── RouteServiceProvider.php          ← Registra las rutas del módulo
├── database/
│   ├── migrations/                           ← Tablas que pertenecen a este módulo
│   └── seeders/                              ← Datos iniciales (opcional)
├── resources/
│   └── views/
│       └── index.blade.php                   ← Vista principal del módulo
├── routes/
│   └── web.php                               ← Rutas del módulo
├── module.json                               ← Nombre, alias y providers del módulo
└── composer.json                             ← Autoload del módulo
```

---

## El layout compartido

Todas las páginas heredan de `resources/views/components/layouts/app.blade.php`. Este archivo define:

- La barra lateral (280 px, fondo `#fbf9f8`, borde dorado `#D4C19C`)
- La barra superior con búsqueda y datos del usuario logueado
- El área de contenido principal (el `{{ $slot }}`)
- Los estilos globales (Material Symbols, fuentes, CSS del panel lateral)

Las vistas de cada módulo lo usan así:

```blade
<x-layouts.app title="Nombre de la página">
    {{-- Todo el contenido va aquí --}}
</x-layouts.app>
```

---

## Cómo agregar un módulo nuevo

### 1. Generar el andamiaje

```bash
php artisan module:make Telefonos
```

Esto crea toda la estructura de carpetas automáticamente.

### 2. Agregar la ruta principal

Edita `Modules/Telefonos/routes/web.php`:

```php
<?php
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('telefonos')->name('telefonos.')->group(function () {
    Route::get('/', function () {
        return view('telefonos::index');
    })->name('index');
});
```

### 3. Crear la vista

Edita `Modules/Telefonos/resources/views/index.blade.php`:

```blade
<x-layouts.app title="Teléfonos — IMJUVE">
<div class="p-8">
    <h2 class="text-[32px] font-bold text-[#621132]">Extensiones Telefónicas</h2>
    {{-- Tu contenido aquí --}}
</div>
</x-layouts.app>
```

### 4. Agregar al sidebar

Edita `resources/views/components/layouts/app.blade.php`, en la sección de navegación:

```blade
<a href="{{ route('telefonos.index') }}"
   class="{{ request()->is('telefonos*') ? 'text-[#621132] font-bold border-r-4 border-[#621132] bg-[#eae8e7]' : 'text-[#544246] hover:bg-[#eae8e7]' }}
          flex items-center gap-3 px-5 py-3 text-sm transition-all">
    <span class="material-symbols-outlined text-xl">phone</span>
    Teléfonos
</a>
```

### 5. Crear la migración (si necesitas una tabla nueva)

```bash
php artisan module:make-migration create_nueva_tabla_table Telefonos
```

Edita el archivo generado, luego:

```bash
php artisan migrate
```

### 6. Habilitar el módulo

```bash
php artisan module:enable Telefonos
```

---

## Reglas que no se deben romper

**1. Un módulo no importa código de otro módulo.**  
Si necesitas datos de otra tabla, haz la query directamente con `DB::table()`. No llames a modelos de otro módulo.

**2. Los modelos del dominio de negocio van en `app/Models/`.**  
Si el modelo se usa en más de un módulo (por ejemplo `Empleado`), debe vivir en `app/Models/Empleado.php`, no dentro de un módulo específico.

**3. El layout compartido es de Core. No lo copies ni lo modifiques en los módulos.**  
Si necesitas algo en el layout (un nuevo ítem en el sidebar, por ejemplo), modifica `resources/views/components/layouts/app.blade.php`.

**4. Las migraciones tienen orden.**  
Si la tabla B tiene una FK a la tabla A, la migración de A debe tener un timestamp menor que B. Los archivos actuales usan la convención `2025_01_01_000001_`, `2025_01_01_000002_`, etc.

---

## Panel lateral de detalle (patrón compartido)

Todos los módulos usan el mismo patrón para el panel deslizante de 400 px:

```html
<!-- El panel (inicialmente cerrado) -->
<div class="fixed top-0 right-0 h-screen w-[400px] bg-white shadow-2xl 
            border-l border-[#D4C19C] z-[60] detail-panel closed" id="mi-panel">
    ...contenido del panel...
</div>

<!-- El backdrop oscuro -->
<div class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[55] hidden" 
     id="mi-backdrop" onclick="cerrarPanel()"></div>
```

```javascript
function abrirPanel(id) {
    document.getElementById('mi-panel').classList.remove('closed');
    document.getElementById('mi-backdrop').classList.remove('hidden');
}
function cerrarPanel() {
    document.getElementById('mi-panel').classList.add('closed');
    document.getElementById('mi-backdrop').classList.add('hidden');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarPanel(); });
```

El CSS de la transición está en el layout compartido:
```css
.detail-panel { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
.detail-panel.closed { transform: translateX(100%); }
```
