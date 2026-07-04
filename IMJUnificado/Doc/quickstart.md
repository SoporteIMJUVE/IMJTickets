# Quickstart — Levantar IMJUnificado desde cero

Este documento te lleva de "nunca he tocado este proyecto" a tener el sistema corriendo en tu máquina en menos de 10 minutos.

---

## Requisitos previos

Antes de empezar necesitas tener instalado:

| Herramienta | Versión mínima | Cómo verificar |
|---|---|---|
| PHP | 8.2 o superior | `php -v` |
| Composer | 2.x | `composer -V` |
| Node.js | 18 o superior | `node -v` |
| npm | 9 o superior | `npm -v` |
| Git | cualquiera | `git --version` |

Para **desarrollo local** no necesitas MySQL — el proyecto usa SQLite (un solo archivo).  
Para **producción en Windows Server** necesitas XAMPP con MySQL.

---

## Paso 1 — Clonar el repositorio

El código vive en el repositorio de IMJTickets, dentro del subdirectorio `IMJUnificado/`:

```bash
git clone <URL-del-repo-IMJTickets>
cd IMJTickets/IMJUnificado
```

Si ya tienes el repositorio clonado:

```bash
cd /ruta/al/repo/IMJTickets
git checkout developer
cd IMJUnificado
```

---

## Paso 2 — Instalar dependencias PHP

```bash
composer install
```

Esto descarga todas las librerías de PHP (Laravel, módulos, PhpSpreadsheet, etc.) en la carpeta `vendor/`. Puede tardar 1-2 minutos la primera vez.

---

## Paso 3 — Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

El archivo `.env` contiene la configuración local (base de datos, correo, etc.). Nunca se sube a git porque tiene credenciales.

El `key:generate` genera una clave de cifrado única para tu instalación. Es obligatorio.

### Configuración de base de datos (desarrollo local — SQLite)

El `.env.example` ya viene configurado para SQLite. Solo necesitas crear el archivo vacío:

```bash
touch database/database.sqlite
```

### Configuración para producción (MySQL en Windows Server)

Abre `.env` y cambia estas líneas:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=imjunificado
DB_USERNAME=root
DB_PASSWORD=tu_contraseña_xampp
```

Crea la base de datos en phpMyAdmin antes de continuar:
```sql
CREATE DATABASE imjunificado CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

## Paso 4 — Crear las tablas y cargar datos reales

```bash
php artisan migrate:fresh --seed
```

Este comando:
1. Borra todas las tablas existentes (si las hay)
2. Crea las 12 tablas del sistema desde cero
3. Carga los datos reales desde los Excel institucionales (`Base-de-Datos/*.xlsx`)

Al terminar verás algo como:
```
Empleados: 19 insertados.
laptop (Laptop): 93 equipos.
PC Especializadas (PC Especializada): 23 equipos.
PC Avanzadas (PC Avanzada): 67 equipos.
Impresoras: 10 insertadas.
Insumos: 8 insertados.
IPs totales: 491 registros.
```

> **Importante:** los seeders leen los archivos Excel desde `../Base-de-Datos/`. Si moviste el proyecto a otro lugar, actualiza la ruta en `database/seeders/DepartamentosSeeder.php` (y los demás seeders).

---

## Paso 5 — Instalar dependencias de frontend y compilar

```bash
npm install
npm run build
```

`npm install` descarga Tailwind CSS, DaisyUI y las herramientas de compilación.  
`npm run build` genera el CSS y JS optimizados en `public/build/`.

Para desarrollo con recarga automática al guardar cambios:
```bash
npm run dev
```
(déjalo corriendo en una terminal separada mientras desarrollas)

---

## Paso 6 — Iniciar el servidor

```bash
php artisan serve
```

Abre tu navegador en: **http://localhost:8000**

---

## Primer login

| Campo | Valor |
|---|---|
| Email | `admin@imjuventud.gob.mx` |
| Contraseña | `admin123` |

> Cambia la contraseña inmediatamente en producción.

---

## Verificar que todo funciona

Después de entrar, verifica estos puntos:

```
✅ Dashboard muestra: 19 empleados, 183 equipos, IPs en uso
✅ CRM → lista 19 empleados con su departamento y extensión
✅ Kardex → pestaña Equipos muestra laptops y PCs con número de inventario
✅ Kardex → pestaña Insumos muestra los toners con estado OK/Crítico
✅ Network → Rangos muestra 11 áreas con barras de capacidad
✅ Network → Inventario muestra ~491 IPs con filtro por área y estado
```

Si alguno falla, revisa los [errores comunes](#errores-comunes).

---

## Comandos útiles del día a día

```bash
# Iniciar el servidor
php artisan serve

# Recompilar CSS/JS después de cambiar vistas
npm run build

# Ver todas las rutas registradas
php artisan route:list

# Ver todos los módulos activos
php artisan module:list

# Resetear BD y recargar datos (útil en desarrollo)
php artisan migrate:fresh --seed

# Crear un nuevo módulo
php artisan module:make NombreModulo

# Ver logs de errores
tail -f storage/logs/laravel.log
```

---

## Errores comunes

### "could not find driver" al migrar
PHP no tiene el driver de SQLite habilitado.  
**Solución:** en `php.ini` descomenta `extension=pdo_sqlite`

### "SQLSTATE: Base table or view not found"
Las migraciones no se han corrido.  
**Solución:** `php artisan migrate:fresh --seed`

### La página se ve sin estilos (sin CSS)
El frontend no está compilado.  
**Solución:** `npm run build`

### "Class not found" en un módulo
El autoload de Composer no está actualizado.  
**Solución:** `composer dump-autoload`

### Los seeders fallan con "File not found"
Los Excel de datos no están donde el seeder los busca.  
**Solución:** verifica que `../Base-de-Datos/*.xlsx` exista relativo a la carpeta `IMJUnificado/`. Si el proyecto está en otra ubicación, actualiza la ruta en `database/seeders/DepartamentosSeeder.php`.

### Login dice "credenciales incorrectas"
La base de datos fue reseteada y el usuario admin no existe.  
**Solución:** `php artisan migrate:fresh --seed` crea el admin con password `admin123`.
