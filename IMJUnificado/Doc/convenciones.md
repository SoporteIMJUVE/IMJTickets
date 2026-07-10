# Convenciones del Proyecto — IMJUnificado

Este archivo es el contrato de código del proyecto. Todo becario o desarrollador que trabaje aquí debe leerlo antes de tocar una línea. Si algo que ves en el código no sigue estas reglas, es deuda técnica — corrígelo cuando puedas.

---

## 1. La regla más importante: MVC estricto

MVC significa **Modelo → Vista → Controlador**. La analogía más simple:

| Parte | Responsabilidad | En este proyecto |
|---|---|---|
| **Modelo** | Los datos y cómo se guardan/leen | Archivos en `app/Models/` y `Modules/X/app/Models/` |
| **Vista** | Lo que el usuario ve | Archivos `.blade.php` en `resources/views/` |
| **Controlador** | El intermediario: recibe la petición, consulta el modelo, envía datos a la vista | Archivos en `Http/Controllers/` |

**La vista no consulta la base de datos. El archivo de rutas no consulta la base de datos. Solo el controlador habla con la base de datos.**

### ❌ MAL — lógica en el archivo de rutas

```php
// routes/web.php — NO HAGAS ESTO
Route::get('/crm', function () {
    $empleados = DB::table('empleados')
        ->leftJoin('departamentos', ...)
        ->get();
    return view('crm::index', ['empleados' => $empleados]);
})->name('crm.index');
```

### ✅ BIEN — el archivo de rutas solo apunta al controlador

```php
// routes/web.php — ASÍ SE HACE
Route::get('/', [CRMController::class, 'index'])->name('index');
```

```php
// CRMController.php — la lógica aquí
public function index()
{
    $empleados = DB::table('empleados')->leftJoin(...)->get();
    return view('crm::index', compact('empleados'));
}
```

> **Por qué importa:** si el día de mañana necesitas reutilizar esa consulta en otra ruta, o agregar lógica de permisos, o escribir un test — con la lógica en el controlador es trivial. En el archivo de rutas es imposible de reutilizar y difícil de leer.

---

## 2. Nombres de rutas

El patrón es `modulo.accion`. Laravel tiene 7 acciones estándar:

| Acción | Método HTTP | URL | Qué hace |
|---|---|---|---|
| `index` | GET | `/tickets` | Lista todos los registros |
| `create` | GET | `/tickets/create` | Muestra el formulario de creación |
| `store` | POST | `/tickets` | Guarda el nuevo registro |
| `show` | GET | `/tickets/{id}` | Muestra un registro específico |
| `edit` | GET | `/tickets/{id}/edit` | Formulario de edición |
| `update` | PUT/PATCH | `/tickets/{id}` | Guarda los cambios |
| `destroy` | DELETE | `/tickets/{id}` | Elimina el registro |

No inventes nombres. Si la operación encaja en alguna de estas 7, úsala.

```php
// Así se registran las 7 rutas de un módulo de una sola línea:
Route::resource('tickets', TicketsController::class);

// O si solo necesitas algunas:
Route::resource('tickets', TicketsController::class)->only(['index', 'create', 'store']);
```

---

## 3. Nombres de vistas

El patrón es `modulo::nombre`. En Blade, `tickets::create` apunta al archivo:
```
Modules/Tickets/resources/views/create.blade.php
```

| Vista | Archivo |
|---|---|
| `tickets::index` | `Modules/Tickets/resources/views/index.blade.php` |
| `tickets::create` | `Modules/Tickets/resources/views/create.blade.php` |
| `tickets::show` | `Modules/Tickets/resources/views/show.blade.php` |

---

## 4. Estructura de un controlador

Todos los controladores siguen este esqueleto. No agregues métodos con nombres raros — si la operación no encaja en los 7 estándar, pregunta antes de crear algo nuevo.

```php
<?php

namespace Modules\MiModulo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MiModuloController extends Controller
{
    // GET /mimodulo — lista
    public function index()
    {
        $datos = DB::table('mi_tabla')->get();
        return view('mimodulo::index', compact('datos'));
    }

    // GET /mimodulo/create — formulario de creación
    public function create()
    {
        return view('mimodulo::create');
    }

    // POST /mimodulo — guardar nuevo
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campo' => 'required|string|max:255',
        ]);

        DB::table('mi_tabla')->insert($validated);

        return redirect()->route('mimodulo.index')
            ->with('success', 'Registro creado correctamente.');
    }

    // GET /mimodulo/{id} — ver detalle
    public function show(int $id)
    {
        $registro = DB::table('mi_tabla')->where('id', $id)->firstOrFail();
        return view('mimodulo::show', compact('registro'));
    }

    // PUT /mimodulo/{id} — actualizar
    public function update(Request $request, int $id)
    {
        $validated = $request->validate(['campo' => 'required|string']);
        DB::table('mi_tabla')->where('id', $id)->update($validated);
        return redirect()->route('mimodulo.index')->with('success', 'Actualizado.');
    }

    // DELETE /mimodulo/{id} — eliminar
    public function destroy(int $id)
    {
        DB::table('mi_tabla')->where('id', $id)->delete();
        return redirect()->route('mimodulo.index')->with('success', 'Eliminado.');
    }
}
```

---

## 5. Vistas Blade — reglas de oro

### El layout compartido

Toda vista del sistema usa `<x-layouts.app>`. No crees layouts propios en cada módulo.

```blade
{{-- Así empieza TODA vista interna --}}
<x-layouts.app title="Mi Módulo — IMJUVE CRM">

    <div class="p-8">
        {{-- Tu contenido aquí --}}
    </div>

</x-layouts.app>
```

### Variables disponibles sin importarlas

Estas variables existen en todas las vistas automáticamente:

| Variable | Qué es |
|---|---|
| `auth()->user()` | El usuario logueado (nombre, email, role) |
| `$errors` | Errores de validación del formulario anterior |
| `session('success')` | Mensaje de éxito del redirect anterior |

### Mostrar errores de validación

Siempre junto al campo que falló. Nunca en un bloque separado arriba del formulario.

```blade
<input name="correo" value="{{ old('correo') }}"
    class="{{ $errors->has('correo') ? 'border-red-400' : 'border-[#E5E7EB]' }}">

@error('correo')
    <p class="text-xs text-red-600">{{ $message }}</p>
@enderror
```

El `old('correo')` recupera lo que el usuario escribió antes de que fallara la validación — sin él, el formulario se vacía en cada error y es frustrante para el usuario.

### No pongas lógica PHP en las vistas

```blade
{{-- ❌ MAL --}}
@php
    $datos = DB::table('empleados')->get();
@endphp

{{-- ✅ BIEN — el controlador ya pasó $datos a la vista --}}
@foreach($datos as $d)
    ...
@endforeach
```

La excepción son cálculos simples de presentación (`$loop->index`, formatear una fecha, etc.).

---

## 6. Colores y estilos — sistema de diseño

No uses colores arbitrarios. El sistema tiene dos colores institucionales y una paleta de UI:

| Token | Valor | Uso |
|---|---|---|
| Guinda | `#621132` | Color principal — botones, títulos, bordes activos |
| Oro | `#D4C19C` | Bordes decorativos, avatares, detalles |
| Fondo | `#fbf9f8` | Background general |
| Texto principal | `#1b1c1c` | Textos normales |
| Texto secundario | `#544246` | Labels, subtítulos, metadatos |
| Borde UI | `#E5E7EB` | Bordes de tarjetas y tablas |
| Fondo input | `#F9F9F8` | Inputs y selectores |

```blade
{{-- Botón principal --}}
<button class="bg-[#621132] text-white px-4 py-2 rounded hover:opacity-90">
    Acción
</button>

{{-- Ítem activo en sidebar --}}
<a class="text-[#621132] font-bold border-r-4 border-[#621132] bg-[#eae8e7]">
    Módulo activo
</a>

{{-- Badge de estado --}}
<span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-0.5 rounded-full">
    Activo
</span>
```

---

## 7. Íconos

Solo usamos **Material Symbols Outlined** (Google). No uses emojis ni SVG manuales en la UI.

```blade
<span class="material-symbols-outlined">person</span>
<span class="material-symbols-outlined">settings</span>
<span class="material-symbols-outlined">delete</span>
```

Busca el nombre del ícono en: https://fonts.google.com/icons

---

## 8. Mensajes al usuario

### Después de una acción exitosa (redirect)

```php
// En el controlador
return redirect()->route('tickets.index')
    ->with('success', 'Ticket creado correctamente.');
```

```blade
{{-- En la vista --}}
@if(session('success'))
<div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
    {{ session('success') }}
</div>
@endif
```

### Errores de validación

Laravel los maneja automáticamente con `$request->validate([...])`. Si la validación falla, redirige de vuelta al formulario con los errores en `$errors` y los valores en `old()`. No necesitas hacer nada extra.

---

## 9. Deuda técnica conocida (a corregir)

Estas cosas existen en el código actual y **no siguen las convenciones**. Son tareas para las próximas iteraciones:

| Archivo | Problema | Solución |
|---|---|---|
| `Modules/CRM/routes/web.php` | Query de DB en el archivo de rutas | Mover a `CRMController@index` |
| `Modules/Kardex/routes/web.php` | Query de DB en el archivo de rutas | Mover a `KardexController@index` |
| `Modules/Network/routes/web.php` | Query de DB en el archivo de rutas | Mover a `NetworkController@index` |
| `Modules/Core/routes/web.php` | Query de DB en el archivo de rutas | Mover a `CoreController@dashboard` |
| Módulo Mantenimiento | Sin implementar — vista placeholder | Requiere reunión con cliente antes de desarrollar |
