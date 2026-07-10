# Estándar de Vistas — IMJUnificado

Guía de referencia para construir nuevas vistas de módulo de forma consistente con CRM, Tickets y Network.
El archivo base para copiar es `resources/views/stubs/module.blade.php`.

---

## Paleta de colores

| Token          | Hex       | Uso principal                                    |
|----------------|-----------|--------------------------------------------------|
| Guinda         | `#621132` | Títulos, botones primarios, acentos              |
| Guinda oscura  | `#991B1B` | Alertas, estados críticos, botón "Cerrar caso"   |
| Oro            | `#D4C19C` | Bordes decorativos, hover suave, badges          |
| Fondo suave    | `#fbf9f8` | Header del panel lateral, footers                |
| Gris borde     | `#E5E7EB` | Bordes de tablas, separadores                    |
| Gris fondo     | `#F3F4F6` | Fondo de filtros, encabezados de tabla, toggle   |
| Texto secundario | `#544246` | Subtítulos, labels, texto de apoyo              |
| Verde estado   | `#166534` | "Activo", "Ocupada", "Disponible"                |
| Rojo estado    | `#991B1B` | "Baja", "Saturado"                               |
| Naranja estado | `#9A3412` | "Atendiendo", "Lleno"                            |
| Azul estado    | `#1E40AF` | "Abierto", "Libre"                               |

---

## Estructura de una vista de módulo

El orden de las secciones es fijo. Las marcadas como **OPCIONAL** se eliminan si el módulo no las necesita.

```
<x-layouts.app title="Nombre — IMJUVE CRM">
<div class="p-8" id="modulo-page">

    1. Header + toggle de vista
    2. Stats               ← OPCIONAL
    3. VIEW: vista1        ← una o más vistas
       3a. x-tabla-encabezado (filtros + acciones)
       3b. tabla
    4. VIEW: vista2        ← OPCIONAL
    ...

</div>

5. Panel lateral (aside)  ← fuera del div.p-8
6. Backdrop

<script>...</script>
</x-layouts.app>
```

---

## 1 — Header + toggle de vista

```blade
{{-- Header + toggle de vista --}}
<div class="flex justify-between items-end mb-6">
    <div>
        <h2 class="text-[32px] font-bold leading-10 tracking-tight text-[#621132]">
            Nombre del Módulo
        </h2>
        <p class="text-[#544246] text-sm mt-1">Descripción breve en un renglón.</p>
    </div>
    <div class="flex items-center gap-3">

        {{-- Toggle de vista — OPCIONAL: eliminar si solo hay una vista --}}
        <div class="flex bg-[#F3F4F6] rounded-lg p-1">
            <button onclick="setView('vista1')" id="btn-vista1"
                    class="px-4 py-2 rounded-md text-sm font-bold transition-all bg-white text-[#621132] shadow-sm">
                Vista 1
            </button>
            <button onclick="setView('vista2')" id="btn-vista2"
                    class="px-4 py-2 rounded-md text-sm font-bold transition-all text-[#544246] hover:bg-[#eae8e7]">
                Vista 2
            </button>
        </div>

        {{-- Botón de ajustes — siempre presente, reemplazar alert cuando esté listo --}}
        <button onclick="alert('Ajustes del módulo aún no disponibles.')"
                class="p-2 border border-[#E5E7EB] rounded-lg hover:bg-[#F3F4F6] transition-colors text-[#544246]"
                title="Ajustes">
            <span class="material-symbols-outlined text-sm">settings</span>
        </button>

    </div>
</div>
```

### Estilo del toggle

El botón **activo** lleva `bg-white text-[#621132] shadow-sm`.
El botón **inactivo** lleva `text-[#544246] hover:bg-[#eae8e7]`.
Ambos comparten `px-4 py-2 rounded-md text-sm font-bold transition-all`.

> No usar `border-b-2` ni subrayado. El recuadro blanco sobre fondo gris es el estándar.

El JS para cambiar el estado activo sigue este patrón (copiar en cada módulo que tenga toggle):

```js
function setView(mode) {
    // reemplaza ['vista1','vista2'] con los IDs reales
    ['vista1', 'vista2'].forEach(v => {
        document.getElementById('view-' + v).classList.toggle('hidden', v !== mode);
    });
    const active   = 'px-4 py-2 rounded-md text-sm font-bold transition-all bg-white text-[#621132] shadow-sm';
    const inactive = 'px-4 py-2 rounded-md text-sm font-bold transition-all text-[#544246] hover:bg-[#eae8e7]';
    document.getElementById('btn-vista1').className = mode === 'vista1' ? active : inactive;
    document.getElementById('btn-vista2').className = mode === 'vista2' ? active : inactive;
}
```

---

## 2 — Stats (OPCIONAL)

KPIs en grid de 3 o 4 columnas arriba del contenido principal. Usar solo si el módulo tiene métricas relevantes que el usuario necesita ver de un vistazo.

```blade
<div class="grid grid-cols-3 gap-6 mb-8">
    <div class="bg-white p-6 rounded-xl border border-[#E5E7EB] shadow-sm">
        <p class="text-[11px] font-bold uppercase tracking-wider text-[#544246] mb-2">Etiqueta</p>
        <h3 class="text-3xl font-bold text-[#621132]">{{ $valor }}</h3>
        <div class="mt-4 flex items-center gap-2 text-[#166534] text-xs">
            <span class="material-symbols-outlined text-sm">check_circle</span>
            Descripción corta
        </div>
    </div>
    ...
</div>
```

Para métricas con barra de progreso, reemplazar el `div` de abajo por:
```html
<div class="mt-4 h-2 bg-[#F3F4F6] rounded-full overflow-hidden">
    <div class="h-full bg-[#D4C19C] rounded-full" style="width:{{ $pct }}%"></div>
</div>
```

---

## 3 — Vistas y tablas

Cada vista va dentro de `<div id="view-nombre">`. La primera vista no lleva `hidden`; las demás sí.

### 3a — Componente `x-tabla-encabezado`

Siempre envuelto en `<div class="bg-white border border-[#E5E7EB] rounded-xl overflow-hidden shadow-sm">`.

```blade
<x-tabla-encabezado
    titulo="Título de la Tabla"
    tab="identificador-unico"
    exportUrl="{{ route('modulo.exportar') }}"
    {{-- :importar="false"  si el módulo no admite importar --}}
>
    <x-slot:acciones>
        {{-- Botones extra: Nuevo, Registrar, etc. Va a la derecha del título --}}
    </x-slot:acciones>

    <x-slot:filtros>
        {{-- Controles de filtro: select, input, date --}}
        {{-- El id de cada control debe coincidir con la clave que espera el Exporter --}}
    </x-slot:filtros>
</x-tabla-encabezado>
```

**Props del componente:**

| Prop        | Tipo    | Defecto | Descripción                                              |
|-------------|---------|---------|----------------------------------------------------------|
| `titulo`    | string  | —       | Título que aparece en la barra                           |
| `tab`       | string  | `tabla` | Identificador único por instancia (ej. `crm`, `equipos`) |
| `conteo`    | mixed   | `null`  | Si se pasa, muestra un contador junto al título          |
| `exportUrl` | string  | `null`  | URL de la ruta de exportación; sin ella el botón Exportar queda deshabilitado |
| `importar`  | bool    | `true`  | `false` oculta el botón Importar (ej. Tickets)           |

**Slots:**

- `$acciones` — se renderiza antes de los botones Importar/Filtrar/Exportar.
- `$filtros` — panel colapsable debajo de la barra. Si no se pasa, el panel no existe.

### IDs de filtros y Exporters

Los IDs de los `<select>` e `<input>` del slot `:filtros` son los mismos que recibe el Exporter en `$filters`. Ejemplo:

```html
<select id="f-eq-tipo" ...>   →   $filters['f-eq-tipo']  en EquiposExporter
<input  id="crm-search" ...>  →   $filters['crm-search'] en EmpleadosExporter
```

### 3b — Tabla

```blade
<div class="bg-white rounded-xl border border-[#E5E7EB] shadow-sm overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-[#F3F4F6] border-b border-[#E5E7EB]">
            <tr>
                <th class="px-6 py-4 text-[11px] font-bold uppercase tracking-wider text-[#544246]">
                    Columna
                </th>
            </tr>
        </thead>
        <tbody id="modulo-tbody" class="divide-y divide-[#E5E7EB]">
            @forelse($registros as $r)
            <tr class="hover:bg-[#D4C19C]/5 transition-colors cursor-pointer modulo-row"
                data-id="{{ $r->id }}"
                onclick="openPanel(this)">
                <td class="px-6 py-4 text-sm">{{ $r->nombre }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="X" class="px-6 py-10 text-center text-[#544246] text-sm">
                    Sin registros
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
```

**Badges de estado:**

```html
<!-- Verde -->
<span class="bg-[#166534]/10 text-[#166534] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Activo</span>
<!-- Rojo -->
<span class="bg-[#991B1B]/10 text-[#991B1B] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Baja</span>
<!-- Naranja -->
<span class="bg-[#9A3412]/10 text-[#9A3412] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Atendiendo</span>
<!-- Azul -->
<span class="bg-[#1E40AF]/10 text-[#1E40AF] px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Abierto</span>
```

---

## 4 — Panel lateral

Siempre **fuera** del `<div class="p-8">`, al final del archivo antes de `</x-layouts.app>`.

```blade
<aside class="fixed top-0 right-0 h-screen w-[400px] bg-white shadow-2xl border-l border-[#D4C19C] z-[60] detail-panel closed flex flex-col"
       id="modulo-panel">

    {{-- Header fijo --}}
    <div class="p-6 border-b border-[#E5E7EB] bg-[#fbf9f8] flex justify-between items-center shrink-0">
        ...
        <button onclick="closePanel()">
            <span class="material-symbols-outlined">close</span>
        </button>
    </div>

    {{-- Tabs — OPCIONAL --}}
    <div class="flex border-b border-[#E5E7EB] px-6 bg-white shrink-0">
        <button class="px-4 py-3 text-[#621132] font-bold border-b-2 border-[#621132] text-sm"
                onclick="switchPanelTab('info', this)">Info</button>
        <button class="px-4 py-3 text-[#544246] font-medium text-sm hover:text-[#621132]"
                onclick="switchPanelTab('historial', this)">Historial</button>
    </div>

    {{-- Body scrollable --}}
    <div class="flex-1 overflow-y-auto custom-scrollbar">
        <div class="p-6 space-y-6" id="tab-info">...</div>
        <div class="p-6 hidden" id="tab-historial">...</div>
    </div>

    {{-- Footer fijo --}}
    <div class="p-6 border-t border-[#E5E7EB] bg-[#F3F4F6] flex gap-3 shrink-0">
        <button class="flex-1 py-3 bg-white border border-[#D4C19C] text-[#621132] font-bold rounded-lg text-sm">
            Editar
        </button>
        <button class="flex-1 py-3 bg-[#621132] text-white font-bold rounded-lg text-sm hover:opacity-90">
            Acción principal
        </button>
    </div>
</aside>

{{-- Backdrop --}}
<div class="fixed inset-0 bg-black/20 backdrop-blur-sm z-[55] hidden"
     id="modulo-backdrop" onclick="closePanel()"></div>
```

El panel se abre/cierra con la clase `closed` (definida en el CSS global de `app.blade.php`):

```js
function openPanel(row) {
    // poblar campos del panel con row.dataset.*
    document.getElementById('modulo-panel').classList.remove('closed');
    document.getElementById('modulo-backdrop').classList.remove('hidden');
}
function closePanel() {
    document.getElementById('modulo-panel').classList.add('closed');
    document.getElementById('modulo-backdrop').classList.add('hidden');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closePanel(); });
```

Tabs del panel (si aplica):

```js
function switchPanelTab(tab, btn) {
    ['info', 'historial'].forEach(t => document.getElementById('tab-' + t).classList.add('hidden'));
    document.querySelectorAll('#modulo-panel .flex.border-b button').forEach(b => {
        b.className = 'px-4 py-3 text-[#544246] font-medium text-sm hover:text-[#621132]';
    });
    document.getElementById('tab-' + tab).classList.remove('hidden');
    if (btn) btn.className = 'px-4 py-3 text-[#621132] font-bold border-b-2 border-[#621132] text-sm';
}
```

---

## 5 — Sistema de exportación

Cada módulo que exporta necesita:

1. **Una ruta** en `Modules/Nombre/routes/web.php`:
```php
Route::get('/exportar', fn(Request $r) => (new \App\Exports\NombreExporter())->download($r->all()))
     ->name('exportar');
```

2. **Un Exporter** en `app/Exports/NombreExporter.php` que extienda `BaseExporter`:
```php
class NombreExporter extends BaseExporter {
    public function headers(): array { return ['Col 1', 'Col 2', ...]; }
    public function filename(): string { return 'nombre_modulo'; }
    public function rows(array $filters): array {
        // $filters tiene las claves = IDs de los filtros del HTML
    }
}
```

3. **`exportUrl`** en el componente `x-tabla-encabezado`:
```blade
exportUrl="{{ route('modulo.exportar') }}"
```

`?solo_encabezados=1` en la URL descarga solo los encabezados (formato vacío para importar).

---

## 6 — Botón de ajustes

Presente en **todos** los módulos, en el header junto al toggle (o solo si no hay toggle).
Mientras no esté implementado, muestra un alert:

```blade
<button onclick="alert('Ajustes del módulo aún no disponibles.')"
        class="p-2 border border-[#E5E7EB] rounded-lg hover:bg-[#F3F4F6] transition-colors text-[#544246]"
        title="Ajustes">
    <span class="material-symbols-outlined text-sm">settings</span>
</button>
```

Cuando se implemente, reemplazar el `onclick` con la función o ruta correspondiente.

---

## Módulos actuales y su estado

| Módulo      | Toggle de vista      | Stats | Panel lateral | Exportar | Importar |
|-------------|----------------------|-------|---------------|----------|----------|
| CRM         | —                    | KPIs  | Sí (tabs)     | Sí       | Sí       |
| Tickets     | Lista / Kanban       | —     | Sí            | Sí       | No       |
| Network     | Rangos / Inventario  | KPIs  | Sí            | Sí       | Sí       |
| Kardex      | Tabs por pestaña     | —     | —             | Equipos / Insumos (Resguardos pendiente) | Sí |
| Telefonos   | —                    | —     | —             | —        | —        |
| Impresoras  | —                    | —     | —             | —        | —        |
| Mantenimiento | —                  | —     | —             | —        | —        |
