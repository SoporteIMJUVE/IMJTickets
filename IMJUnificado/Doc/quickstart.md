# Quickstart — Levantar IMJUnificado

---

## Requisitos

| Herramienta | Versión mínima | Verificar con |
|---|---|---|
| PHP | 8.2 o superior | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 18 o superior | `node -v` |

> **En Windows con XAMPP:** PHP y Composer normalmente ya están disponibles en el PATH después de instalar XAMPP. Si no, agrégalos manualmente: `C:\xampp\php` para PHP y la ruta de Composer según tu instalación.

---

## Respaldos de sistemas legados

IMJUnificado fusiona dos sistemas previos. Los respaldos deben estar en `DB_source/` (un nivel arriba de `IMJUnificado/`):

```
IPMJ_proyect/
├── DB_source/
│   ├── sistemitas.sql     ← dump PostgreSQL del Sistema Inventario (disponible)
│   └── imjtickets.sql     ← dump MySQL de IMJTickets (pendiente de obtener del servidor Windows)
└── IMJTikets/IMJTickets/IMJUnificado/
```

El setup detecta automáticamente cuáles están disponibles e importa lo que encuentra.

---

## Opción A — Setup automático (recomendado)

```bash
cd IMJUnificado
bash setup.sh
```

El script hace en orden:

1. Instala dependencias PHP con Composer
2. Crea el archivo `.env` a partir de `.env.example` (si no existe)
3. Genera la clave de la aplicación (`APP_KEY`)
4. Crea el archivo de base de datos SQLite vacío (si no existe)
5. Corre todas las migraciones (crea las tablas)
6. Corre `db:seed` — crea el usuario admin y catálogos de tickets
7. Corre `app:boot --force` — importa empleados, equipos, IPs e insumos desde `sistemitas.sql`
8. Instala dependencias de Node.js y compila el frontend

Cuando termine:

```bash
composer dev
```

Abre **http://localhost:8000**  
Login: `admin@imjuventud.gob.mx` / `admin123`

---

## Opción B — Setup manual (paso a paso)

### Paso 1 — Instalar dependencias PHP

```bash
cd IMJUnificado
composer install
```

### Paso 2 — Crear el archivo de entorno

```bash
cp .env.example .env
php artisan key:generate
```

`.env` no se sube al repositorio (está en `.gitignore`). Por defecto ya apunta a SQLite para desarrollo local.

### Paso 3 — Crear la base de datos local

```bash
touch database/database.sqlite
```

En MySQL esto no es necesario — la base de datos se crea en el servidor.

### Paso 4 — Correr migraciones

```bash
php artisan migrate
```

Crea todas las tablas. Si quieres empezar desde cero usa `migrate:fresh` en su lugar.

### Paso 5 — Crear usuario admin y catálogos

```bash
php artisan db:seed
```

Crea el usuario `admin@imjuventud.gob.mx`, los tipos de ticket y las áreas.

### Paso 6 — Importar datos desde el respaldo

```bash
php artisan app:boot --force
```

Lee `DB_source/sistemitas.sql` e importa: departamentos, empleados, equipos de cómputo, teléfonos, impresoras, insumos y rangos IP. Si `imjtickets.sql` también está disponible, importa tickets y áreas de ese sistema.

### Paso 7 — Instalar dependencias de Node.js y compilar

```bash
npm install
npm run build     # producción
# o
npm run dev       # desarrollo con recarga automática
```

### Paso 8 — Iniciar el servidor

```bash
composer dev   # servidor + Vite + logs en paralelo
```

---

## Arranque normal (después del setup)

```bash
cd IMJUnificado
composer dev
```

`composer dev` lanza en paralelo:
- `php artisan serve` — servidor web en http://localhost:8000
- `npm run dev` — Vite con recarga automática
- `php artisan pail` — log de la aplicación en tiempo real
- `php artisan queue:listen` — procesador de trabajos en cola

Para cerrarlo todo: `Ctrl + C`

---

## Cuando llegue el dump de IMJTickets

Cuando obtengas el respaldo del servidor Windows:

1. Cópialo a `DB_source/imjtickets.sql`
2. Corre:

```bash
php artisan app:boot --force
```

Importará los tickets, áreas y tipos del sistema legado, y fusionará los empleados por correo con los ya existentes.

---

## En Windows Server (producción con XAMPP)

El proceso es idéntico al de desarrollo local, solo cambia la configuración de base de datos en el `.env`.

**1. Crear la base de datos en MySQL** — abre phpMyAdmin (`http://localhost/phpmyadmin`):
```sql
CREATE DATABASE imjunificado CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**2. Editar el `.env`** para apuntar a MySQL:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=imjunificado
DB_USERNAME=root
DB_PASSWORD=            # vacía por defecto en XAMPP
```

**3. Correr el setup:**
```bash
bash setup.sh
```

**4. Iniciar el servidor:**
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

`--host=0.0.0.0` hace que el servidor sea accesible desde otros equipos en la red local.

---

## Comandos del día a día

```bash
# Iniciar todo (servidor + Vite + logs)
composer dev

# Solo el servidor PHP
php artisan serve

# Reimportar datos desde los respaldos legados
php artisan app:boot --force

# Resetear BD completa y volver a importar todo
php artisan migrate:fresh --force && php artisan db:seed && php artisan app:boot --force

# Ver todas las rutas registradas
php artisan route:list

# Ver módulos activos
php artisan module:list

# Ver errores de la app en tiempo real
php artisan pail

# Compilar CSS/JS para producción
npm run build
```

---

## Errores comunes

**"credenciales incorrectas" al hacer login**

La base de datos fue reseteada y el usuario admin no existe. Ejecuta:

```bash
php artisan db:seed
```

Login: `admin@imjuventud.gob.mx` / `admin123`

---

**La tabla tiene cero registros después del setup**

El `app:boot` no encontró el archivo `sistemitas.sql`. Verifica que exista en:

```
IPMJ_proyect/DB_source/sistemitas.sql
```

Luego vuelve a correr:
```bash
php artisan app:boot --force
```

---

**`npm run dev` falla con "missing specifier in laravel-vite-plugin"**

Estás corriendo el comando desde el directorio padre en lugar de `IMJUnificado/`:

```bash
cd IMJUnificado
npm install
npm run dev
```

---

**`could not find driver` al migrar**

PHP no tiene el driver habilitado. Abre el `php.ini` y descomenta:

```ini
extension=pdo_sqlite   ; desarrollo local
extension=pdo_mysql    ; producción con XAMPP
```

Reinicia el servidor después de cambiar `php.ini`.

---

**La página se ve sin estilos**

El CSS no está compilado:

```bash
npm run build   # producción
# o
npm run dev     # desarrollo
```
