# Stack Tecnológico

Cada tecnología cumple un rol específico. Este documento explica qué hace cada una, por qué se eligió sobre las alternativas, y dónde aprender lo básico si eres nuevo en ella.

---

## PHP 8.2

**Qué es:** el lenguaje de programación del backend. Todo el código del servidor está escrito en PHP.

**Por qué PHP y no Python/JavaScript/etc.:**  
El sistema de tickets original (IMJTickets) ya estaba en PHP y en producción. Cambiar de lenguaje habría significado reescribir todo desde cero sin nada nuevo que justificara el costo. PHP 8.2 además tiene tipado fuerte, atributos nativos y rendimiento comparable a otros lenguajes modernos.

**Recursos para aprender:**
- [PHP en 100 segundos (video)](https://www.youtube.com/watch?v=a7_WFUlFS94)
- [PHP The Right Way](https://phptherightway.com/) — guía de buenas prácticas, en inglés
- [Manual oficial de PHP](https://www.php.net/manual/es/) — en español

---

## Laravel 12

**Qué es:** el framework PHP. Laravel pone orden en el proyecto: define dónde va cada archivo, cómo se conectan las rutas con los controladores, cómo se habla con la base de datos, cómo se genera HTML, etc.

**Conceptos clave de Laravel que usamos en este proyecto:**

| Concepto | Para qué sirve en IMJUnificado |
|---|---|
| **Rutas** (`routes/web.php`) | Define qué URL muestra qué página |
| **Controladores** (`app/Http/Controllers/`) | Lógica que procesa cada petición |
| **Vistas Blade** (`resources/views/`) | Plantillas HTML con lógica mínima |
| **Migraciones** (`database/migrations/`) | Define y versiona la estructura de la BD |
| **Seeders** (`database/seeders/`) | Carga datos iniciales en la BD |
| **Eloquent ORM** | Habla con la BD como si fueran objetos PHP |
| **Artisan** | CLI del proyecto (`php artisan ...`) |

**Por qué Laravel 12:**  
Ya lo usaba IMJTickets en producción. El equipo (aunque rotativo) ya tenía contexto con él. Laravel tiene la mejor documentación de cualquier framework PHP.

**Recursos para aprender:**
- [Laravel en 100 segundos (video)](https://www.youtube.com/watch?v=9OKbmMqsREc)
- [Documentación oficial de Laravel](https://laravel.com/docs/12.x) — la mejor documentación de cualquier framework
- [Laracasts — Laravel desde cero](https://laracasts.com/series/30-days-to-learn-laravel-11) — curso gratuito de 30 lecciones
- [Laravel Bootcamp](https://bootcamp.laravel.com/) — tutorial oficial paso a paso

---

## nwidart/laravel-modules (v13)

**Qué es:** un paquete que agrega "módulos" a Laravel. Normalmente en Laravel todo está en una sola estructura de carpetas. Con este paquete, cada funcionalidad vive en su propia carpeta autónoma con sus rutas, controladores, vistas y migraciones independientes.

**Cómo funciona:**

```
Sin módulos:              Con módulos (este proyecto):
routes/web.php            Modules/CRM/routes/web.php
  ├── /crm               Modules/Tickets/routes/web.php
  ├── /tickets           Modules/Kardex/routes/web.php
  └── /kardex            ...cada módulo es un "mini-Laravel"
```

**Por qué es importante para este proyecto:**  
El equipo es rotativo. Un becario puede trabajar en el módulo de Kardex sin entender ni tocar el módulo de Tickets. Si introduce un bug en su módulo, los demás siguen funcionando. Es la diferencia entre construir un castillo de arena (todo junto) y construir con bloques LEGO (módulos separados).

**Comandos clave:**
```bash
php artisan module:list              # Lista todos los módulos y su estado
php artisan module:make NuevoModulo  # Crea un módulo nuevo desde cero
php artisan module:enable NuevoModulo
php artisan module:disable NuevoModulo
```

**Recursos:**
- [Documentación oficial de nwidart/laravel-modules](https://nwidart.com/laravel-modules/v6/introduction)

---

## Livewire 3

**Qué es:** una librería que permite hacer interfaces reactivas (que cambian sin recargar la página) usando solo PHP, sin necesidad de escribir JavaScript.

**La idea principal:** normalmente, para actualizar parte de una página sin recargarla necesitas JavaScript (Vue, React, etc.). Con Livewire escribes un componente PHP que tiene propiedades y métodos, y Livewire se encarga de sincronizarlo con el HTML en el navegador.

**Ejemplo conceptual:**
```php
// Sin Livewire: necesitas PHP + JavaScript + fetch + JSON
// Con Livewire: solo PHP
class BuscadorEmpleados extends Component {
    public string $busqueda = '';

    public function render() {
        return view('livewire.buscador', [
            'empleados' => Empleado::where('nombre', 'like', "%{$this->busqueda}%")->get()
        ]);
    }
}
```

**Estado actual en IMJUnificado:** las vistas actuales usan JavaScript vanilla para los paneles laterales y filtros. Livewire está instalado y listo para usarse cuando se refactoricen los módulos.

**Recursos:**
- [Livewire en 100 segundos (video)](https://www.youtube.com/watch?v=f4QShF42c6E)
- [Documentación oficial de Livewire 3](https://livewire.laravel.com/docs/quickstart)
- [Screencasts de Livewire](https://livewire.laravel.com/screencasts)

---

## Blade (motor de plantillas)

**Qué es:** el sistema de plantillas de Laravel. Los archivos `.blade.php` son HTML con directivas especiales de PHP.

**Sintaxis básica que se usa en este proyecto:**

```blade
{{-- Comentario que no aparece en el HTML final --}}

{{ $variable }}              {{-- Muestra variable con escape HTML --}}
{!! $html_sin_escapar !!}    {{-- Solo usar con contenido confiable --}}

@if($condicion)
    ...
@elseif($otra)
    ...
@else
    ...
@endif

@foreach($lista as $item)
    <li>{{ $item->nombre }}</li>
@endforeach

@forelse($lista as $item)
    <li>{{ $item->nombre }}</li>
@empty
    <p>No hay elementos</p>
@endforelse

{{-- Componente reutilizable --}}
<x-layouts.app title="Mi página">
    contenido aquí
</x-layouts.app>

{{-- Incluir otra vista --}}
@include('partials.header')
```

**Recursos:**
- [Documentación de Blade](https://laravel.com/docs/12.x/blade)

---

## Tailwind CSS v4

**Qué es:** un framework de CSS que funciona con clases utilitarias. En lugar de escribir CSS en archivos separados, aplicas clases directamente en el HTML.

**Cómo funciona:**
```html
<!-- CSS tradicional -->
<div class="tarjeta">...</div>
<style>.tarjeta { padding: 24px; border-radius: 8px; background: white; }</style>

<!-- Con Tailwind -->
<div class="p-6 rounded-xl bg-white">...</div>
```

**Clases de Tailwind más usadas en este proyecto:**

| Clase | Qué hace |
|---|---|
| `p-6`, `px-4`, `py-2` | padding (todos lados / horizontal / vertical) |
| `m-4`, `mb-8` | margin (todos / abajo) |
| `flex`, `grid` | layout flexbox / grid |
| `gap-4` | espacio entre elementos en flex/grid |
| `text-sm`, `text-lg` | tamaño de fuente |
| `font-bold` | negrita |
| `text-[#621132]` | color exacto con valor hex |
| `bg-white`, `bg-[#fbf9f8]` | color de fondo |
| `border`, `border-[#E5E7EB]` | borde |
| `rounded-xl` | esquinas redondeadas |
| `w-full`, `h-screen` | ancho/alto |
| `hidden` | ocultar elemento |
| `hover:bg-[#eae8e7]` | estilo al pasar el mouse |

**Recursos:**
- [Tailwind CSS en 100 segundos (video)](https://www.youtube.com/watch?v=mr15Xzb1Ook)
- [Documentación oficial de Tailwind CSS](https://tailwindcss.com/docs)
- [Tailwind Play — probar clases en línea](https://play.tailwindcss.com/)

---

## DaisyUI

**Qué es:** un plugin de componentes para Tailwind CSS. Agrega clases semánticas como `btn`, `badge`, `modal`, `card` encima de Tailwind.

**Ejemplo:**
```html
<!-- Tailwind puro -->
<button class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Click</button>

<!-- DaisyUI -->
<button class="btn btn-primary">Click</button>
```

En este proyecto DaisyUI se usa poco directamente — la mayoría de los componentes usan clases de Tailwind con los colores institucionales (`#621132`, `#D4C19C`). DaisyUI está disponible si necesitas componentes de formularios, modals o tablas rápidamente.

**Recursos:**
- [Documentación de DaisyUI](https://daisyui.com/components/)

---

## Vite (compilador de frontend)

**Qué es:** la herramienta que procesa y empaqueta el CSS y JavaScript del proyecto.

**Por qué importa:** Tailwind CSS v4 no es un archivo estático — necesita ser procesado para generar solo las clases que realmente se usan en las vistas. Vite hace ese proceso.

**Comandos:**
```bash
npm run dev    # Servidor de desarrollo con recarga en vivo (para desarrollar)
npm run build  # Compilar para producción (para desplegar)
```

**Cuándo ejecutarlo:** cada vez que cambies algo en `resources/css/app.css` o agregues nuevas clases de Tailwind en las vistas Blade.

---

## SQLite / MySQL

**SQLite** (desarrollo local): una base de datos que vive en un solo archivo (`database/database.sqlite`). No requiere instalar ningún servidor. Perfecto para desarrollo local y pruebas.

**MySQL** (producción): el motor de base de datos del servidor Windows. Requiere XAMPP o MySQL instalado. Se configura en el `.env` con `DB_CONNECTION=mysql`.

**La clave:** Laravel abstrae la diferencia. El mismo código PHP funciona con ambos. Solo cambia el `.env`.

**Recursos para entender SQL:**
- [SQL en 100 segundos (video)](https://www.youtube.com/watch?v=zsjvFFKOm3c)
- [Tutorial de MySQL — W3Schools](https://www.w3schools.com/mysql/)

---

## PhpSpreadsheet / Maatwebsite Excel

**Qué es:** librerías para leer y escribir archivos Excel (`.xlsx`) desde PHP.

**Cómo se usa en este proyecto:**

- **Lectura (seeders):** los seeders (`DepartamentosSeeder`, `EmpleadosSeeder`, etc.) usan `PhpSpreadsheet` directamente para leer los Excel institucionales y cargar los datos en la BD.
- **Escritura (exportaciones):** cuando se implemente la exportación de reportes, se usará `Maatwebsite/Excel` para generar archivos `.xlsx` formateados.

**Recursos:**
- [Documentación de PhpSpreadsheet](https://phpspreadsheet.readthedocs.io/)
- [Maatwebsite/Excel para Laravel](https://laravel-excel.com/)

---

## Git y GitHub

El repositorio usa Git con la siguiente estrategia de ramas:

| Rama | Propósito |
|---|---|
| `main` | Código en producción. Solo se toca cuando hay un release probado. |
| `developer` | Rama de integración. Aquí se unifica el trabajo. |
| `legado-base-datos` | Preserva el historial del sistema de inventario original. |
| `feature/nombre` | Una rama por funcionalidad nueva. Se mergea a `developer`. |

**Flujo recomendado para un becario:**
```bash
git checkout developer
git pull origin developer          # Traer cambios recientes
git checkout -b feature/mi-tarea  # Crear tu rama
# ... trabajar ...
git add .
git commit -m "Descripción clara de qué hiciste y por qué"
git push origin feature/mi-tarea
# Crear Pull Request en GitHub hacia developer
```

**Recursos:**
- [Git en 100 segundos (video)](https://www.youtube.com/watch?v=hwP7WQkmECE)
- [Aprende Git Branching — interactivo](https://learngitbranching.js.org/?locale=es_ES)
