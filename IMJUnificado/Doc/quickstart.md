# Quickstart — Levantar IMJUnificado

---

## Requisitos

Estos deben estar instalados en la máquina antes de cualquier otro paso.

| Herramienta | Versión mínima | Verificar con |
|---|---|---|
| PHP | 8.2 o superior | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 18 o superior | `node -v` |

> **En Windows con XAMPP:** PHP y Composer normalmente ya están disponibles en el PATH después de instalar XAMPP. Si no, agrégalos manualmente: `C:\xampp\php` para PHP y la ruta de Composer según tu instalación.

---

## Opción A — Setup automático (recomendado)

Un solo script configura todo desde cero.

```bash
cd IMJUnificado
bash setup.sh
```

El script hace en este orden:

1. Instala dependencias PHP con Composer
2. Crea el archivo `.env` a partir de `.env.example` (si no existe)
3. Genera la clave de la aplicación (`APP_KEY`)
4. Crea el archivo de base de datos SQLite vacío (si no existe)
5. Corre todas las migraciones (crea las tablas)
6. Corre los seeders (carga los datos reales desde los archivos Excel)
7. Instala dependencias de Node.js
8. Compila el CSS y JavaScript para producción

Cuando termine, inicia el servidor:

```bash
composer dev
```

Abre **http://localhost:8000**  
Login: `admin@imjuventud.gob.mx` / `admin123`

---

## Opción B — Setup manual (paso a paso)

Útil si el script falla en algún punto y necesitas saber exactamente en qué paso quedaste, o si quieres entender qué hace cada parte.

### Paso 1 — Instalar dependencias PHP

```bash
cd IMJUnificado
composer install
```

Esto lee `composer.json` y descarga todos los paquetes de PHP en la carpeta `vendor/`. Tarda 1–3 minutos la primera vez.

### Paso 2 — Crear el archivo de entorno

```bash
cp .env.example .env
php artisan key:generate
```

`.env` contiene la configuración local (base de datos, claves, etc.) y **no se sube al repositorio** (está en `.gitignore`). `key:generate` genera un valor aleatorio para `APP_KEY` que Laravel usa para encriptar sesiones.

Por defecto el `.env.example` ya apunta a SQLite. No necesitas cambiarlo para desarrollo local.

### Paso 3 — Crear la base de datos local

```bash
touch database/database.sqlite
```

SQLite guarda toda la base de datos en un solo archivo. Este comando lo crea vacío. En MySQL no es necesario este paso — la base de datos se crea en el servidor de base de datos.

### Paso 4 — Correr migraciones y seeders

```bash
php artisan migrate:fresh --seed
```

- `migrate:fresh` borra todas las tablas y las vuelve a crear desde cero usando los archivos en `database/migrations/`
- `--seed` ejecuta automáticamente los seeders después: carga empleados, equipos, IPs, impresoras e insumos desde los archivos Excel en `Base-de-Datos/`

Al terminar verás en la terminal cuántos registros se insertaron:

```
Inventario Equipos: 183 registros en total.
Rangos IPs: 11 insertados.
IPs totales: 491 registros.
```

### Paso 5 — Instalar dependencias de Node.js

```bash
npm install
```

Descarga Vite, Tailwind, DaisyUI y todo lo necesario para compilar el frontend. Se guarda en `node_modules/`. **Importante:** este `npm install` debe correrse dentro de `IMJUnificado/`, no en el directorio padre (`IMJTickets/`), ya que son proyectos Node independientes.

### Paso 6 — Compilar CSS y JavaScript

Para desarrollo (con recarga automática al guardar):
```bash
npm run dev
```

Para producción (genera archivos optimizados en `public/build/`):
```bash
npm run build
```

En desarrollo local usa `npm run dev` en una segunda terminal mientras el servidor PHP corre. En producción usa `npm run build` una sola vez antes de desplegar.

### Paso 7 — Iniciar el servidor

```bash
php artisan serve
```

O para lanzar todo junto (servidor + Vite + logs en paralelo):

```bash
composer dev
```

---

## Arranque normal (después del setup)

Una vez que el setup ya se hizo, solo necesitas:

```bash
cd IMJUnificado
composer dev
```

`composer dev` lanza en paralelo en la misma terminal:
- `php artisan serve` — servidor web en http://localhost:8000
- `npm run dev` — Vite con recarga automática al guardar vistas o CSS
- `php artisan pail` — log de la aplicación en tiempo real

Para cerrarlo todo: `Ctrl + C`

---

## En Windows Server (producción con XAMPP)

El proceso es **idéntico** al de desarrollo local, solo cambia la configuración de base de datos en el `.env`.

### Diferencias respecto al setup de desarrollo

**1. Crear la base de datos en MySQL** — abre phpMyAdmin (`http://localhost/phpmyadmin`):
```sql
CREATE DATABASE imjunificado CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**2. Editar el `.env`** para apuntar a MySQL en lugar de SQLite:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=imjunificado
DB_USERNAME=root
DB_PASSWORD=            # contraseña de MySQL, vacía por defecto en XAMPP
```

El resto del `.env.example` funciona igual.

**3. Correr el setup** — igual que en desarrollo, con cualquiera de las dos opciones:

```bash
# Opción A (automático)
bash setup.sh

# Opción B (manual) — mismos 7 pasos de arriba
```

> En Windows, `bash` requiere Git Bash o WSL. Git Bash viene incluido con Git para Windows.

**4. Iniciar el servidor:**
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

`--host=0.0.0.0` hace que el servidor sea accesible desde otros equipos en la red local, no solo desde `localhost`. Úsalo en el servidor Windows para que los empleados puedan acceder desde sus computadoras.

> **Para producción formal:** en lugar de `artisan serve`, configura un VirtualHost en Apache (que ya viene con XAMPP) apuntando al directorio `public/` del proyecto. Eso es más estable y permite múltiples sitios en el mismo servidor. Ver [documentación de despliegue de Laravel](https://laravel.com/docs/deployment).

---

## Comandos del día a día

```bash
# Iniciar todo (servidor + Vite + logs)
composer dev

# Solo el servidor PHP, sin Vite (más ligero en producción)
php artisan serve

# Resetear BD y recargar datos desde los Excel
php artisan migrate:fresh --seed

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

**`npm run dev` falla con "missing specifier in laravel-vite-plugin"**

Estás corriendo `npm run dev` desde el directorio padre (`IMJTickets/`) en vez de desde `IMJUnificado/`. Los `node_modules` del padre son incompatibles.

```bash
cd IMJUnificado      # asegúrate de estar aquí
npm install          # crea el node_modules propio de este proyecto
npm run dev
```

**`composer install` falla con "your php version does not satisfy"**

Tienes PHP 8.5+ y alguna dependencia vieja aún tiene restricción `<8.5`. El `composer.json` actual ya usa versiones compatibles, pero si actualizas dependencias manualmente puedes reintroducirlo:

```bash
composer update phpoffice/phpspreadsheet --with-dependencies
```

**`could not find driver` al migrar**

PHP no tiene el driver de SQLite (o MySQL) habilitado. Abre el `php.ini` de tu instalación y busca y descomenta la línea correspondiente:

```ini
extension=pdo_sqlite   ; para SQLite (desarrollo local)
extension=pdo_mysql    ; para MySQL (producción con XAMPP)
```

Reinicia el servidor después de cambiar `php.ini`.

**La página se ve sin estilos (todo en blanco/texto plano)**

El CSS no está compilado. Ejecuta:

```bash
npm run build
```

O si estás en desarrollo, inicia Vite:

```bash
npm run dev
```

**"credenciales incorrectas" al hacer login**

La base de datos fue reseteada (o es nueva) y el seeder no corrió. El usuario admin no existe. Ejecuta:

```bash
php artisan migrate:fresh --seed
```

Login: `admin@imjuventud.gob.mx` / `admin123`

**El seeder falla al leer los archivos Excel**

Los archivos Excel deben estar en `../Base-de-Datos/` relativo al directorio `IMJUnificado/`. Verifica que la estructura del repositorio sea:

```
IMJTickets/
├── Base-de-Datos/
│   ├── NUEVO INVENTARIO IMJUVE  ABRIL 2026.1xlsx.xlsx
│   ├── SOLICITUDES TONER Y STOCK.xlsx
│   └── Inventario IPS.xlsx
└── IMJUnificado/      ← aquí corres los comandos
```

Si clonaste solo `IMJUnificado/` sin el repositorio completo, los seeders no encontrarán los Excel. Clona `IMJTickets` completo.
