# Quickstart — Levantar IMJUnificado

---

## Requisitos

| Herramienta | Versión mínima | Verificar con |
|---|---|---|
| PHP | 8.2 o superior | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 18 o superior | `node -v` |

---

## Primera vez (setup completo)

```bash
cd IMJUnificado
bash setup.sh
```

Eso es todo. El script hace en orden: instalar dependencias PHP, crear `.env`, crear la base de datos, correr migraciones, cargar datos reales, instalar Node, compilar CSS/JS.

Cuando termine, inicia el servidor:

```bash
composer dev
```

Abre **http://localhost:8000**  
Login: `admin@imjuventud.gob.mx` / `admin123`

---

## Arranque normal (después del setup)

```bash
cd IMJUnificado
composer dev
```

`composer dev` lanza en paralelo:
- `php artisan serve` — el servidor web en puerto 8000
- `npm run dev` — Vite con recarga automática al guardar vistas
- `php artisan pail` — log en vivo en la terminal

Para cerrarlo todo: `Ctrl + C`

---

## En Windows Server (producción con XAMPP)

El proceso es idéntico pero con MySQL en lugar de SQLite.

**1. Requisitos previos en el servidor:**
- XAMPP instalado con PHP 8.2+ y MySQL corriendo
- Composer y Node.js instalados globalmente
- El repositorio clonado en `C:\xampp\htdocs\IMJTickets\` (o donde lo tengas)

**2. Crear la base de datos** — abre phpMyAdmin:
```sql
CREATE DATABASE imjunificado CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**3. Configurar `.env`** — edita el archivo `.env` en `IMJUnificado/`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=imjunificado
DB_USERNAME=root
DB_PASSWORD=          # la contraseña de tu XAMPP, vacía por defecto
```

**4. Correr el setup:**
```bash
cd IMJUnificado
bash setup.sh
```

**5. Iniciar el servidor:**
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

Con `--host=0.0.0.0` el sistema es accesible desde otros equipos en la red local.

> Para producción real considera un servidor web como Apache o Nginx + PHP-FPM en lugar de `artisan serve`. XAMPP ya tiene Apache — [ver cómo configurar un VirtualHost](https://laravel.com/docs/deployment#nginx).

---

## Comandos del día a día

```bash
# Iniciar todo (servidor + Vite + logs)
composer dev

# Solo el servidor PHP (sin Vite, más ligero)
php artisan serve

# Resetear BD y recargar datos desde los Excel
php artisan migrate:fresh --seed

# Ver todas las rutas
php artisan route:list

# Ver módulos activos
php artisan module:list

# Ver errores en tiempo real
php artisan pail

# Compilar CSS/JS para producción
npm run build
```

---

## Errores comunes

**`npm run dev` falla con "missing specifier"**  
Estás usando el `node_modules` del directorio padre (IMJTickets). Soluciónalo:
```bash
cd IMJUnificado
npm install   # crea el node_modules propio del proyecto
npm run dev
```

**`composer install` falla con "your php version does not satisfy"**  
Tu PHP es 8.5+ y alguna dependencia tiene restricción de versión antigua:
```bash
composer update phpoffice/phpspreadsheet --with-dependencies
```
Esto ya está corregido en el `composer.json` actual — solo ocurre si actualizas las dependencias manualmente.

**`could not find driver` al migrar**  
PHP no tiene el driver de SQLite habilitado. En `php.ini` busca y descomenta:
```
extension=pdo_sqlite
```

**La página se ve sin estilos**  
El CSS no está compilado:
```bash
npm run build
```

**"credenciales incorrectas" al hacer login**  
La BD fue reseteada pero el seeder no corrió. Ejecuta:
```bash
php artisan migrate:fresh --seed
```
Esto crea el usuario `admin@imjuventud.gob.mx` / `admin123`.
