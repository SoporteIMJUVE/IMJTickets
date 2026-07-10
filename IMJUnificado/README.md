# IMJUnificado

Sistema de administración de TI del Instituto Mexicano de la Juventud.  
Unifica el módulo de tickets de soporte con el inventario de equipos, IPs, empleados e insumos en una sola plataforma.

---

## Para el becario que acaba de llegar

Este sistema fue construido por becarios de TI. Si acabas de aterrizar y no sabes por dónde empezar, **lee esto primero y después sigue el orden de la tabla de contenidos**.

No asumimos que sabes PHP, ni Laravel, ni qué es un "entorno". Si sabes programar en cualquier otro lenguaje (Python, JavaScript, Java) ya tienes el 70% del conocimiento que necesitas — el otro 30% son los conceptos específicos de este stack.

---

## Tabla de Contenidos

| Documento | Cuándo leerlo |
|---|---|
| **Este README** | Primero — da el panorama general |
| [Doc/quickstart.md](Doc/quickstart.md) | Cuando quieras levantar el proyecto |
| [Doc/convenciones.md](Doc/convenciones.md) | Antes de escribir tu primera línea de código |
| [Doc/stack.md](Doc/stack.md) | Cuando quieras entender qué tecnología hace qué |
| [Doc/arquitectura.md](Doc/arquitectura.md) | Cuando necesites entender cómo está organizado el código |
| [Doc/base-de-datos.md](Doc/base-de-datos.md) | Cuando necesites entender las tablas y sus relaciones |
| [Doc/estandar-vistas.md](Doc/estandar-vistas.md) | ayuda para diseño de UI y UX |
| [Doc/modulo-tickets.md](Doc/modulo-tickets.md) | Cuando vayas a tocar el módulo de tickets |
| [Doc/modulo-crm.md](Doc/modulo-crm.md) | Cuando vayas a tocar el directorio de empleados |
| [Doc/modulo-kardex.md](Doc/modulo-kardex.md) | Cuando vayas a tocar equipos o insumos |
| [Doc/modulo-network.md](Doc/modulo-network.md) | Cuando vayas a tocar el módulo de IPs |
| [Doc/decisiones.md](Doc/decisiones.md) | Para entender por qué el sistema funciona como funciona |

---

## El problema que resuelve

Antes de este sistema existían dos herramientas separadas:

| Sistema anterior | Tecnología | Problema |
|---|---|---|
| IMJTickets | PHP + Laravel + MySQL | Solo manejaba tickets de soporte, sin inventario |
| Sistema de Inventario | Python + Streamlit + PostgreSQL | Solo inventario, sin gestión de soporte |

Los técnicos tenían que revisar dos sistemas por separado para responder una pregunta tan simple como "¿qué equipo tiene este empleado y cuántos tickets abiertos tiene?".

IMJUnificado junta todo en un solo sistema con una sola base de datos.

---

## El stack — qué tecnología hace qué

Si nunca has programado en PHP, esta tabla te da el equivalente en JavaScript para que te orientes:

| Tecnología | Para qué sirve | Equivalente en JS |
|---|---|---|
| **PHP** | El lenguaje del servidor — procesa peticiones, consulta la BD, genera el HTML | Node.js |
| **Laravel** | El framework — te da estructura, rutas, autenticación, migraciones. Sin él tendrías que inventar todo desde cero | Express.js + muchos middlewares |
| **Composer** | El manejador de paquetes de PHP | npm (para PHP) |
| **Blade** | El motor de plantillas — los archivos `.blade.php` que generan el HTML | EJS, Jinja2 |
| **Vite + Node.js** | Compila y optimiza el CSS y JavaScript del frontend | Webpack/Vite |
| **Tailwind CSS** | Clases de CSS utilitarias para dar estilos directo en el HTML | Bootstrap (pero más flexible) |
| **SQLite** | Base de datos local — un solo archivo, sin servidor | Igual (SQLite es universal) |
| **MySQL** | Base de datos en producción (Windows Server) | Igual |

---

## Qué es un "entorno" (`.env`)

Cuando el código dice `DB_HOST` o `APP_KEY`, esos valores vienen de un archivo llamado `.env` que vive en la raíz del proyecto y **no se sube al repositorio** (está en `.gitignore` por seguridad).

El `.env` contiene configuración que cambia entre máquinas:
- En tu laptop: base de datos SQLite local
- En el servidor de producción: base de datos MySQL del servidor

El `.env.example` es la plantilla — tiene todos los nombres de variables pero sin los valores reales. Cuando clonas el proyecto lo copias:

```bash
cp .env.example .env
```

Luego llenas los valores según tu entorno.

---

## Qué es una "migración"

Una migración es un archivo PHP que describe la estructura de una tabla de la base de datos. En lugar de entrar a phpMyAdmin y crear la tabla manualmente, describes la estructura en código y la ejecutas:

```bash
php artisan migrate
```

Ventaja: todos en el equipo tienen la misma estructura de BD, y los cambios quedan en el historial de git.

Si quieres ver cómo se ven las migraciones de este proyecto:
```
IMJUnificado/database/migrations/
```

---

## Qué es un "seeder"

Un seeder carga datos iniciales en la base de datos. En este proyecto los seeders leen los archivos Excel del inventario y los insertan en las tablas.

```bash
php artisan migrate:fresh --seed
```

Ese comando borra todas las tablas, las recrea y ejecuta todos los seeders. Al final tienes la BD lista con:
- 19 empleados (leídos desde Excel)
- 183 equipos de cómputo (leídos desde Excel)
- 491 IPs registradas (leídas desde Excel)
- 10 impresoras, 8 insumos, 1 usuario admin

---

## Cómo está organizado el código

```
IMJUnificado/
├── Modules/              ← cada módulo del sistema vive aquí
│   ├── Core/             ← login, logout, dashboard, layout compartido
│   ├── Tickets/          ← gestión de tickets de soporte
│   ├── CRM/              ← directorio de empleados
│   ├── Kardex/           ← inventario de equipos e insumos
│   ├── Network/          ← IPs y direccionamiento de red
│   └── Mantenimiento/    ← reportes de mantenimiento (stub, requiere reunión con cliente)
│
├── app/                  ← código base de Laravel (modelos compartidos)
│   └── Models/User.php   ← el modelo del técnico/admin
│
├── database/
│   ├── migrations/       ← estructura de las tablas
│   └── seeders/          ← datos iniciales desde Excel
│
├── resources/views/      ← layout compartido y componentes globales
│   └── components/
│       └── layouts/
│           └── app.blade.php  ← el sidebar + topbar que envuelve TODAS las vistas
│
├── routes/
│   └── web.php           ← solo la ruta raíz "/" (redirige a login o dashboard)
│
├── public/               ← lo único que el navegador accede directamente
│   └── build/            ← CSS y JS compilados por Vite
│
└── Doc/                  ← toda la documentación del proyecto
```

Dentro de cada módulo la estructura es siempre la misma:

```
Modules/MiModulo/
├── app/
│   ├── Http/Controllers/MiModuloController.php   ← lógica
│   └── Models/MiModelo.php                       ← datos
├── database/
│   ├── migrations/                               ← tablas del módulo
│   └── seeders/                                  ← datos iniciales
├── resources/views/
│   ├── index.blade.php                           ← vista principal
│   └── create.blade.php                          ← formulario
└── routes/
    └── web.php                                   ← rutas del módulo
```

---

## El flujo de una petición HTTP

Cuando el navegador pide `GET /tickets`, esto pasa en orden:

```mermaid
flowchart LR
    A[Navegador] -->|GET /tickets| B[Router]
    B -->|¿Tiene sesión?| C{Middleware auth}
    C -->|No| D[Redirect a /login]
    C -->|Sí| E[TicketsController@index]
    E -->|query| F[(Base de datos)]
    F -->|datos| E
    E -->|compact datos| G[index.blade.php]
    G -->|HTML generado| A
```

Este diagrama aplica a **todos** los módulos. El router siempre consulta el middleware, el middleware decide si permite el paso, el controlador consulta la BD y pasa los datos a la vista, la vista genera el HTML.

---

## Estado del proyecto

| Módulo | Ruta | Estado |
|---|---|---|
| Login / Logout | `/login` | ✅ Completo |
| Dashboard | `/dashboard` | ✅ Completo |
| Tickets — formulario público | `/tickets/create` | ✅ Completo |
| Tickets — gestión interna | `/tickets` | ✅ Kanban + lista + panel lateral con comentarios |
| CRM (empleados) | `/crm` | ✅ CRUD completo + panel lateral con equipos e IPs reales |
| Kardex (equipos e insumos) | `/kardex` | ✅ Lectura + filtros + panel detalle AJAX + subida de resguardos PDF |
| Network (IPs) | `/network` | 🔧 Vista lista — edición pendiente |
| Mantenimiento | `/mantenimiento` | ⏳ Stub — requiere definición de alcance |

---

## Lo que viene — prioridades para el próximo becario

Por orden de impacto:

1. **Mover queries a controladores** — CRM, Kardex y Network aún tienen consultas de BD en `routes/web.php`. Ver [Doc/convenciones.md](Doc/convenciones.md).

2. **Alta y movimiento de insumos en Kardex** — la tabla `suministros` existe, falta el formulario de entrada/salida de stock.

3. **Alta de nuevo equipo en Kardex** — formulario para registrar un equipo sin PDF de resguardo.

4. **Generar PDF de resguardo desde el sistema** — imprimir el documento oficial desde el panel lateral del equipo (`barryvdh/laravel-dompdf` ya instalado).

5. **Tabs "Historial" y "Tickets" del panel lateral de CRM** — actualmente son placeholder.

6. **Módulo Network — edición de IPs** — asignar/desasignar IP a empleado desde la UI.

7. **Control de roles** — la columna `role` en `users` existe pero no hay middleware que la use.

---

## Historial y créditos

Este sistema unifica dos proyectos con historiales de git distintos:

- **IMJTickets** (rama `main`): sistema original de tickets, en producción en Windows Server.
- **Base-de-Datos** (carpeta `Base-de-Datos/`): sistema de inventario en Python/Streamlit, importado con `git merge --allow-unrelated-histories` para preservar los commits de los becarios originales.
- **IMJUnificado** (carpeta `IMJUnificado/`, rama `developer`): este sistema — la unificación.

Para ver el historial completo: `git log --oneline --all`
