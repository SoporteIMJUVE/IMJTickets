# IMJUnificado — Sistema de TI IMJUVE

Sistema institucional unificado de gestión de TI para el **Instituto Mexicano de la Juventud**.  
Consolida el sistema de tickets de soporte (IMJTickets) y el inventario de equipos, red y personal (Base-de-Datos) en una sola plataforma modular.

---

## Documentación

| Sección | Descripción |
|---|---|
| [Quickstart — levantar en 5 min](Doc/quickstart.md) | Instalación local desde cero, primer login |
| [Stack tecnológico](Doc/stack.md) | Qué hace cada tecnología, por qué se eligió, dónde aprender |
| [Arquitectura modular](Doc/arquitectura.md) | Cómo están organizados los módulos, cómo agregar uno nuevo |
| [Base de datos](Doc/base-de-datos.md) | Tablas, esquema, seeders, cómo correr migraciones |
| [Módulos del sistema](Doc/modulos.md) | Qué hace cada módulo, sus rutas, vistas y datos |
| [Decisiones de diseño](Doc/decisiones.md) | Por qué se tomaron las decisiones de negocio y técnicas |

---

## Contexto del proyecto

Este sistema nació de la necesidad de unificar dos herramientas separadas:

| Sistema original | Stack | Estado |
|---|---|---|
| **IMJTickets** | Laravel 12 + Livewire 3 + MySQL (XAMPP) | En producción en Windows Server |
| **Sistema de Inventario** | Python + Streamlit + PostgreSQL | Uso interno, sin acceso multi-usuario |

Ambos manejaban datos del mismo personal y equipo institucional, pero sin conexión entre sí. Un técnico tenía que abrir dos sistemas para ver si a un empleado le correspondía un ticket y qué equipo tenía asignado.

El equipo de desarrollo es **rotativo** (becarios y servicio social), por lo que la arquitectura prioriza la comprensibilidad y la capacidad de agregar módulos sin romper lo que ya funciona.

---

## Stack elegido

**Laravel 12 + Livewire 3 + MySQL + nwidart/laravel-modules**

| Capa | Tecnología | Por qué |
|---|---|---|
| Backend | PHP 8.2 / Laravel 12 | Ya en producción en IMJTickets; ecosistema enorme; docs excelentes para nuevos devs |
| UI reactiva | Livewire 3 + Blade | Interactividad sin necesidad de una SPA ni una API REST separada; un solo proceso |
| CSS | Tailwind CSS v4 + DaisyUI | Ya configurado en IMJTickets; utilidades directas sin escribir CSS custom |
| Modularidad | nwidart/laravel-modules | Cada módulo es una carpeta autónoma; agregar uno no toca los demás |
| Base de datos | MySQL (local SQLite para dev) | Windows Server ya tiene XAMPP+MySQL; elimina la segunda DB (PostgreSQL) |
| PDF | barryvdh/laravel-dompdf | Ya en IMJTickets; genera resguardos e informes institucionales |
| Excel | Maatwebsite/Excel + PhpSpreadsheet | Importa los Excel de inventario; exporta reportes formateados |

### Por qué NO otras opciones

| Descartado | Razón |
|---|---|
| Mantener Python/Streamlit | No es multi-usuario en producción; dos runtimes = más RAM; sin SSO |
| Next.js/React SPA + API | Complejidad innecesaria para becarios; CORS, tokens, estado en cliente |
| Microservicios | Overkill: múltiples procesos, depuración difícil, overhead de red |
| Django (Python) | Sin continuidad con el stack de producción actual |

---

## Arquitectura — Monolito Modular

```
IMJUnificado/
├── Modules/
│   ├── Core/           ← auth, layout compartido, búsqueda global
│   ├── Tickets/        ← gestión de tickets de soporte (Kanban + lista)
│   ├── CRM/            ← directorio de empleados + panel lateral de activos
│   ├── Kardex/         ← equipos, insumos, resguardos
│   ├── Network/        ← IPs y rangos de red institucional
│   ├── Telefonos/      ← extensiones telefónicas
│   ├── Impresoras/     ← inventario de impresoras
│   └── Mantenimiento/  ← reportes de mantenimiento (UI pendiente)
├── app/                ← solo modelos base y auth (User)
├── resources/views/    ← layout compartido (app.blade.php)
└── database/
    ├── migrations/     ← migraciones globales + las de cada módulo
    └── seeders/        ← datos reales cargados de los Excel institucionales
```

> **Regla de oro para becarios:** cada módulo vive en su carpeta. Tiene sus propias rutas, controladores, vistas y migraciones. Un módulo no importa código de otro (solo usa la BD compartida). Si algo falla en un módulo, los demás siguen funcionando.

Ver detalles en → [Arquitectura modular](Doc/arquitectura.md)

---

## Instalación rápida

```bash
cd IMJUnificado
cp .env.example .env
php artisan key:generate
touch database/database.sqlite   # solo en dev local
php artisan migrate:fresh --seed
npm install && npm run build
php artisan serve
```

Login: `admin@imjuventud.gob.mx` / `admin123`

Guía completa → [Quickstart](Doc/quickstart.md)

---

## Módulos implementados

| Módulo | Ruta | Estado |
|---|---|---|
| Dashboard | `/dashboard` | ✅ Con KPIs reales |
| CRM — Empleados | `/crm` | ✅ Tabla + panel lateral |
| Tickets | `/tickets` | ✅ Kanban + lista |
| Kardex | `/kardex` | ✅ Equipos / Insumos / Resguardos |
| Red e IPs | `/network` | ✅ Rangos + inventario filtrable |
| Teléfonos | `/telefonos` | 🔧 Ruta stub |
| Impresoras | `/impresoras` | 🔧 Ruta stub |
| Mantenimiento | `/mantenimiento` | 🔧 Ruta stub |

Ver detalles de cada módulo → [Módulos del sistema](Doc/modulos.md)

---

## Base de datos — resumen

12 tablas unificadas desde dos sistemas originales:

| Tabla | Origen | Contenido |
|---|---|---|
| `users` | IMJTickets | Técnicos y administradores del sistema |
| `empleados` | Inventario + IMJTickets | Personal institucional (directorio) |
| `departamentos` | Inventario | Áreas/direcciones |
| `telefonos` | Inventario | Extensiones por empleado |
| `tickets` | IMJTickets | Solicitudes de soporte |
| `areas` / `tipos` | IMJTickets | Catálogos de tickets |
| `inventario_equipos` | Inventario | Laptops, PCs (183 equipos) |
| `impresoras` | Inventario | 10 impresoras institucionales |
| `insumos` / `suministros` | Inventario | Toners y consumibles |
| `cat_rangos_ips` | Inventario | 11 rangos de red por área |
| `inventario_ips_completo` | Inventario | 491 IPs registradas |

Ver esquema completo → [Base de datos](Doc/base-de-datos.md)

---

## Características nuevas (vs. los sistemas originales)

Funcionalidades que no existían en ninguno de los dos proyectos anteriores:

- **Panel lateral de 400 px** al seleccionar un empleado: muestra equipo asignado, IP, extensión, historial de tickets
- **Vista Kanban** para tickets (los sistemas originales solo tenían lista)
- **Dashboard unificado** con KPIs de todos los módulos en una sola pantalla
- **UI para catálogos** de Áreas y Tipos de incidente (antes eran botones muertos)
- **Gestión de cuentas técnicas** (antes solo por `php artisan tinker`)
- **Control de roles** real: `admin` / `tecnico` (la columna existía pero nunca se usaba)
- **Datos semilla reales** cargados automáticamente de los Excel institucionales

---

## Diseño institucional

El sistema sigue el sistema de diseño **"Federal Asset Integrity"** definido en los wireframes:

- **Color principal:** guinda `#621132`
- **Acento:** oro `#D4C19C`
- **Tipografía:** Inter (UI) + JetBrains Mono (datos técnicos)
- **Sidebar:** 280 px, fondo claro `#fbf9f8`, borde dorado
- **Panel lateral de detalle:** 400 px, desliza desde la derecha

---

## Historial del proyecto

Este repositorio contiene la historia **completa** de ambos proyectos originales:

- Los commits de los becarios del sistema de inventario (rama `Base-de-Datos`) están preservados con su autoría original mediante `git merge --allow-unrelated-histories`
- El historial de IMJTickets (rama `main`) también está intacto
- El trabajo de unificación está en la rama `developer`

```
developer  ←  merge legado-base-datos  ←  historial Base-de-Datos
    └──────────────────────────────────→  historial IMJTickets (main)
```

---

## Para el próximo becario o colaborador

Si eres nuevo en este proyecto, el camino recomendado es:

1. Lee [Quickstart](Doc/quickstart.md) para levantar el proyecto en tu máquina
2. Lee [Stack tecnológico](Doc/stack.md) — especialmente si no conoces Laravel o Livewire
3. Lee [Arquitectura modular](Doc/arquitectura.md) para entender dónde vive cada cosa
4. Cuando debas agregar algo nuevo, crea un módulo nuevo — **nunca modifiques Core sin consultarlo**
5. Si tienes dudas de por qué algo está como está, revisa [Decisiones de diseño](Doc/decisiones.md)
