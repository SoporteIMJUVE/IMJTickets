# Módulos del Sistema

---

## Core — Base del sistema

**Ruta:** `/dashboard`  
**Archivos clave:**
- `Modules/Core/routes/web.php` — rutas de login y dashboard
- `Modules/Core/Http/Controllers/CoreController.php` — login / logout
- `Modules/Core/resources/views/dashboard.blade.php` — vista del dashboard
- `resources/views/components/layouts/app.blade.php` — layout compartido de toda la app

**Qué hace:**
- Maneja la autenticación (login / logout)
- Muestra el Dashboard con los 4 KPIs principales
- Provee el layout con sidebar y topbar que usan todos los demás módulos

**KPIs en el dashboard:**

| Card | Dato | Fuente |
|---|---|---|
| Usuarios Activos | `COUNT(empleados WHERE activo=true)` | tabla `empleados` |
| Activos Registrados | `COUNT(inventario_equipos)` | tabla `inventario_equipos` |
| Tickets Abiertos | `COUNT(tickets WHERE estado=0)` | tabla `tickets` |
| IPs en Uso | `COUNT(inventario_ips WHERE estatus='Ocupada')` | tabla `inventario_ips_completo` |

---

## CRM — Gestión de Empleados

**Ruta:** `/crm`  
**Archivos clave:**
- `Modules/CRM/routes/web.php` — construye y pasa datos a la vista
- `Modules/CRM/resources/views/index.blade.php` — tabla + panel lateral
- `Modules/CRM/database/migrations/` — tablas `departamentos`, `empleados`, `telefonos`

**Qué hace:**
- Lista todos los empleados con su departamento, equipos asignados y extensión
- Búsqueda en tiempo real por nombre o área (JavaScript en el cliente)
- Panel lateral de 400 px al seleccionar un empleado con:
  - Datos personales y de contacto
  - Tab "Recursos" — equipos y dispositivos asignados
  - Tab "Historial" — (pendiente: conectar con tickets)
  - Tab "Tickets" — (pendiente: conectar con módulo Tickets)

**Query principal:**
```php
DB::table('empleados')
    ->leftJoin('departamentos', 'empleados.id_departamento', '=', 'departamentos.id_departamento')
    ->leftJoin('telefonos', 'empleados.id_empleado', '=', 'telefonos.id_empleado')
    ->leftJoin('inventario_equipos', 'empleados.id_empleado', '=', 'inventario_equipos.id_empleado')
    ->select('empleados.*', 'departamentos.nombre as departamento_nombre',
             'telefonos.extension', DB::raw('COUNT(inventario_equipos.id) as total_equipos'))
    ->groupBy(...)
    ->get();
```

**Estado de implementación:** ✅ Tabla con datos reales. Panel lateral con tabs (datos estáticos por ahora).

---

## Tickets — Soporte Técnico

**Ruta:** `/tickets`  
**Archivos clave:**
- `Modules/Tickets/routes/web.php`
- `Modules/Tickets/resources/views/index.blade.php` — vista Kanban/Lista
- `Modules/Tickets/database/migrations/2025_01_01_000010_create_tickets_table.php`

**Qué hace:**
- Vista Kanban: columnas Abierto / Atendiendo / Cerrado, cada ticket como tarjeta
- Vista Lista: tabla con todos los tickets y filtros
- Panel lateral de detalle al seleccionar un ticket
- Toggle entre vista Kanban y Lista

**Columnas del Kanban:**

```
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│  🔵 Abierto     │  │  🟡 Atendiendo   │  │  🟢 Cerrado     │
│  estado = 0     │  │  estado = 1      │  │  estado = 2     │
│                 │  │                  │  │                 │
│  [Ticket #47]   │  │  [Ticket #45]    │  │  [Ticket #40]   │
│  Nombre         │  │  Nombre          │  │  Nombre         │
│  Soporte HW     │  │  Software        │  │  Red            │
└─────────────────┘  └─────────────────┘  └─────────────────┘
```

**Estado de implementación:** ✅ Vista Kanban y Lista implementadas. Datos de la BD (actualmente 0 tickets — hay que crear el formulario público).

---

## Kardex — Inventario y Resguardos

**Ruta:** `/kardex`  
**Archivos clave:**
- `Modules/Kardex/routes/web.php` — construye queries para las 3 pestañas
- `Modules/Kardex/resources/views/index.blade.php` — 3 tabs + 2 paneles laterales
- `Modules/Kardex/database/migrations/` — `inventario_equipos`, `impresoras`, `insumos`, `suministros`

**Qué hace — 3 pestañas:**

### Pestaña Equipos
- Tabla de todos los equipos con tipo (badge de color), número de inventario, responsable
- Badge verde "Asignado" / azul "Almacén" según `id_empleado`
- Panel lateral al seleccionar (detalles técnicos del equipo)

### Pestaña Insumos
- 4 KPIs: total de piezas en almacén, stock crítico, salidas recientes, valor
- Tabla con part number, descripción, stock actual y estado (OK / Crítico)
- Panel lateral al hacer click mostrando el part number y stock en detalle

### Pestaña Resguardos
- Lista de empleados que tienen al menos 1 activo asignado
- Columna "Total Activos" con el conteo
- Panel lateral genera un resguardo A4 institucional

**Estado de implementación:** ✅ Tres pestañas con datos reales. Paneles laterales básicos.

---

## Network — Gestión de Red e IPs

**Ruta:** `/network`  
**Archivos clave:**
- `Modules/Network/routes/web.php` — queries de rangos e IPs
- `Modules/Network/resources/views/index.blade.php` — tabs Rangos/Inventario
- `Modules/Network/database/migrations/2025_01_01_000007_create_network_tables.php`

**Qué hace — 2 pestañas:**

### Pestaña Rangos
- 3 KPIs: IPs totales, IPs en uso (con barra de progreso), alertas de saturación (>90%)
- Tabla de 11 rangos con barra de capacidad por rango
- Colores semánticos: verde (<70%), naranja (70-90%), rojo (>90%)
- Botón "Ver IPs" que lleva al inventario filtrado por ese rango

### Pestaña Inventario
- Filtros: por estado (Ocupada/Libre/Reservada) y por área (siglas del rango)
- Tabla de ~491 IPs con usuario asignado, tipo de equipo y estado
- Panel lateral con información técnica completa: MAC, tipo conexión, permisos de internet

**Estado de implementación:** ✅ Ambas pestañas con datos reales. Filtros JavaScript funcionales.

---

## Telefonos, Impresoras, Mantenimiento (stubs)

**Rutas:** `/telefonos`, `/impresoras`, `/mantenimiento`

Estos módulos están creados con su estructura completa pero la vista muestra solo un placeholder. Las tablas en la base de datos ya existen (`telefonos`, `impresoras`).

**Para implementarlos:** editar la ruta en `Modules/<Nombre>/routes/web.php` para pasar datos reales, y reemplazar el contenido de `Modules/<Nombre>/resources/views/index.blade.php`.

La estructura de datos ya está disponible:
- `telefonos`: 19 extensiones cargadas del directorio
- `impresoras`: 10 impresoras cargadas del Excel

---

## Módulo de formulario público de tickets (pendiente)

Actualmente el módulo Tickets solo tiene la vista de gestión (para técnicos logueados). Falta la vista pública donde cualquier empleado pueda crear un ticket sin login.

**Lo que hay que crear:**
- Ruta `GET /soporte` — formulario público (sin middleware `auth`)
- Ruta `POST /soporte` — procesa y guarda el ticket
- Vista con el formulario: nombre, correo, área (select de `areas`), tipo (select de `tipos`), descripción
- Validación y mensaje de confirmación con el número de ticket

El formulario NO requiere login — es la interfaz para los solicitantes (empleados que no tienen cuenta en el sistema).
