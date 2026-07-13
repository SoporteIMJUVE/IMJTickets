# Migración `empleados` → `users` — Paso 1

Este documento describe el primer paso de la reestructuración de base de datos
acordada: fusionar la tabla de login (`users`) con el directorio de RRHH
(`empleados`) en una sola tabla, e introducir dos roles reales (`admin` /
`user`) con permisos distintos. Sigue el mismo espíritu que `Doc/arquitectura.md`
— explica cómo quedó el sistema, no solo el plan original.

**Paso 2 (fuera de este documento):** sistema de código de recuperación con
QR/PDF para las cuentas migradas con correo placeholder. No implementado aquí
a propósito — este paso solo deja el terreno listo (columnas, tabla única,
password temporal hasheada).

---

## 1. Por qué existía la duplicación

Antes de este cambio:

| Tabla | Para qué | Problema |
|---|---|---|
| `users` | Login (Laravel Auth nativo) | `role` era texto libre sin validar (`'tecnico'` por defecto), sin helpers de permisos |
| `empleados` | Directorio RRHH usado por CRM/Kardex/Network/Tickets | Sin modelo Eloquent, `correo` nullable y **sin unicidad a nivel BD** — en la práctica, 127 de 129 registros tenían correo nulo o duplicado |

Las dos tablas nunca se cruzaban entre sí: un empleado y un usuario con el
mismo correo eran registros completamente independientes.

## 2. Qué se hizo

### 2.1 Esquema — Lote A (ya aplicado)

`users` absorbió las columnas de `empleados`:

```
apellido_paterno, apellido_materno, puesto, id_departamento (FK),
activo, fecha_alta, fecha_baja
```

`role` cambió su default de `'tecnico'` a `'user'`. Los dos valores válidos
hacia adelante son `App\Models\User::ROLE_ADMIN` (`'admin'`) y `::ROLE_USER`
(`'user'`).

Las tablas que referenciaban `empleados.id_empleado` recibieron una columna
**nueva** `user_id` (FK a `users.id`), **sin quitar todavía** la columna vieja:

- `telefonos.user_id`
- `inventario_equipos.user_id`
- `impresoras.user_id`
- `inventario_ips_completo.user_id`

Migraciones: `database/migrations/2026_07_12_000001_*` y
`Modules/{CRM,Kardex,Network}/database/migrations/2026_07_12_0000{2,3,4,5}_*`.

> **Nota técnica:** el proyecto corre Laravel 13, que ya no depende de
> `doctrine/dbal` — `SQLiteGrammar` reconstruye la tabla de forma nativa para
> `dropColumn`/`renameColumn`/`dropForeign`/`change()`. Las migraciones
> estándar de `Schema::table()` funcionan igual en SQLite (dev) y MySQL
> (prod) sin paquetes extra.

### 2.2 Comando de sincronización

`php artisan app:sync-empleados-users [--dry-run]`
(`app/Console/Commands/SyncEmpleadosToUsersCommand.php` +
`app/Services/EmpleadosToUsersMigrator.php`)

Por cada fila de `empleados`:

1. Si `correo` es válido y único → se usa tal cual.
2. Si no (nulo o duplicado) → correo placeholder determinístico:
   `empleado{id_empleado}@placeholder.imjuve.local`
   (dominio que nunca puede chocar con uno institucional real).
3. Password aleatoria criptográfica, hasheada, nunca logueada en texto plano.
4. `role = 'user'` para todo lo migrado; cualquier `role` preexistente
   distinto de `'admin'` se normaliza a `'user'`.
5. Si ya existe un `users` con ese correo (misma persona con login previo),
   se fusionan las columnas de empleado sobre esa fila en vez de duplicar.
6. Rellena `user_id` en las 4 tablas relacionadas usando el mapeo
   `id_empleado → users.id` recién construido.

Es **idempotente**: correrlo dos veces no duplica nada (detecta por email
determinístico qué ya se migró). `empleados` se queda intacta como tabla de
staging — no se toca en este paso.

Enganchado en:
- `app/Console/Commands/FirstBootCommand.php` (`php artisan app:boot`) — corre
  al final, después de que los importadores legados terminan de poblar
  `empleados`.
- `database/seeders/DatabaseSeeder.php` — corre justo después de
  `EmpleadosSeeder::class`.

**Resultado de la corrida real (2026-07-12):** 129 empleados procesados, 127
con correo placeholder (dato sucio preexistente, no introducido por esta
migración).

### 2.3 Alta de personas hacia adelante

`Modules/CRM/app/Http/Controllers/CRMController.php::store()` usa el mismo
mecanismo: si el operador deja `correo` en blanco, se genera placeholder +
password vía `app/Support/NewAccountProvisioner.php` (helper compartido con
el comando de sincronización).

### 2.4 Roles y autorización

No existía ninguna infraestructura de autorización antes de este cambio (sin
`Gate`, sin `Policy`, sin middleware de roles). Se agregó:

- `App\Models\User::isAdmin()` + constantes `ROLE_ADMIN`/`ROLE_USER`.
- `app/Http/Middleware/EnsureUserIsAdmin.php`, alias `admin` registrado en
  `bootstrap/app.php`.
- Middleware `['auth', 'admin']` en: `Modules/CRM`, `Modules/Kardex`,
  `Modules/Network` (rutas completas), y en `Modules/Core` solo en
  `dashboard`/`search`, y en `Modules/Tickets` solo en el grupo de gestión
  (`index`, `estado`, `comentar`, `asignar`, `conteo`, `exportar`).
- **Sin cambios:** `tickets.create`/`tickets.store` siguen 100% públicas (sin
  ningún middleware) — cualquiera puede seguir abriendo un ticket sin login,
  ahora validando el correo contra `users.email` en vez de `empleados.correo`.

| Ruta | Quién entra |
|---|---|
| `/dashboard`, `/search`, `/crm/*`, `/kardex/*`, `/network/*`, `/tickets` (gestión) | Solo `admin` |
| `/perfil` | Cualquier usuario logueado (`admin` o `user`) |
| `/tickets/create`, `POST /tickets` | Público, sin login |
| `/logout` | Cualquier usuario logueado |

Login (`Modules/Core/app/Http/Controllers/CoreController.php::login()`) y el
redirect de `/` (`routes/web.php`, `Modules/Core/routes/web.php`) ahora
ramifican: `admin` → `dashboard`, `user` → `/perfil` (vista placeholder,
mismo patrón documentado en `Doc/arquitectura.md` §8).

El sidebar (`resources/views/components/layouts/app.blade.php`) muestra el
menú completo solo si `auth()->user()->isAdmin()`; para `user` muestra
únicamente "Perfil" y "Nuevo Ticket". La barra de búsqueda global del topbar
también se oculta para `user` (apuntaba a `/dashboard`, que ahora es
admin-only).

### 2.5 Bugs preexistentes corregidos de paso

Al verificar el omnibuscador (`/search`) con el servidor real se encontraron
y corrigieron dos bugs que ya existían antes de esta migración (no
relacionados con `empleados`, pero en el mismo archivo que se estaba
tocando):

- `ip::text ILIKE ?` — sintaxis específica de PostgreSQL, inválida en
  SQLite. Corregido a `LOWER(ip) LIKE LOWER(?)`.
- Búsqueda de tickets referenciaba una columna inexistente `nombre_reporta`
  (la columna real es `nombre`).

## 3. Compatibilidad hacia atrás (para no romper vistas/JS)

Varias queries mantienen alias con los nombres viejos para minimizar el
churn en Blade/JS que ya esperaba esas claves:

- `users.id as id_empleado`, `users.email as correo`, `users.name as nombre`
  en CRM, Kardex y los exporters.
- Los formularios (`resguardo-preview.blade.php`, CRM) siguen posteando un
  campo `id_empleado`; los controladores lo escriben en la columna nueva
  `user_id`.

## 4. Pendiente — Lote B (destructivo, NO ejecutado)

Migraciones ya creadas pero deliberadamente sin correr:

- `Modules/{CRM,Kardex,Network}/database/migrations/2026_07_12_10000{1,2,3,4}_drop_id_empleado_from_*` — quitan la columna vieja `id_empleado`.
- `database/migrations/2026_07_12_100005_rename_empleados_to_empleados_legacy_backup.php` — renombra `empleados` (no la borra, queda como red de seguridad).

**No correr `php artisan migrate` con estas incluidas hasta:**
1. Verificar en un uso real (no solo pruebas) que todo funciona leyendo/escribiendo `user_id`.
2. Tomar un backup de la base de datos independiente del rename.
3. Confirmación explícita de que ya no hace falta `empleados` como respaldo.

Verificar con `php artisan migrate:status` que siguen en `Pending` antes de
cualquier despliegue.

## 5. Cómo verificar que todo sigue bien

```bash
php artisan migrate:status                          # Lote A: Ran · Lote B: Pending
php artisan app:sync-empleados-users --dry-run       # debe reportar todo "ya migrado"
php artisan route:list --path=crm                    # confirmar middleware admin
```

Manual: login como `admin@imjuventud.gob.mx` → dashboard/CRM/Kardex/Network
deben cargar. Login como una cuenta con `role=user` → debe caer en `/perfil`,
y `/dashboard`, `/crm`, `/kardex`, `/network`, `/tickets` deben dar 403.

Grep de barrido (debe dar cero fuera de `PostgresDumpImporter.php`,
`FirstBootCommand.php`, `EmpleadosSeeder.php`, `EmpleadosToUsersMigrator.php`
y migraciones ya aplicadas):

```bash
grep -rn "DB::table('empleados')" --include="*.php" app Modules
```
