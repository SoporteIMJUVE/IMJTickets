# IMJTickets + Inventario IMJUVE

Sistema de gestión de tickets de soporte técnico y control de inventario para el **Instituto Mexicano de la Juventud**.

Este repositorio contiene dos sistemas independientes que pueden ejecutarse en paralelo y enlazarse mediante un botón de cambio:

| Sistema | Tecnología | Puerto por defecto |
|---|---|---|
| **IMJTickets** — tickets de soporte | Laravel 12 + Livewire | `8000` |
| **Inventario** — equipos, IPs, insumos | Python 3 + Streamlit + PostgreSQL | `8501` |

---

## Requisitos previos

- PHP >= 8.2 + Composer
- Node.js >= 18 + npm
- Python >= 3.10 + pip
- PostgreSQL >= 14 (para el sistema de inventario)
- `psql` disponible en el PATH (para el script de arranque del inventario)

---

## 1. Sistema de Tickets (Laravel)

### Instalación

```bash
# Desde la raíz del repositorio
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Variables de entorno (`.env`)

```
APP_URL=http://localhost:8000

# URL del sistema de inventario Streamlit (activa el botón de cambio en el navbar)
INVENTARIO_URL=http://localhost:8501
```

> **Importante:** `INVENTARIO_URL` debe estar en el `.env` real, no solo en `.env.example`.
> Si el botón de inventario no aparece en el navbar, verifica que esta variable esté definida
> y ejecuta `php artisan config:clear` después de agregarla.

### Arranque

```bash
php artisan serve
```

Accede en: `http://localhost:8000`

---

## 2. Sistema de Inventario (Streamlit)

El inventario conecta a **PostgreSQL**. El script `start.sh` verifica si la base de datos tiene tablas; si está vacía, importa automáticamente el dump antes de levantar la aplicación.

### Instalación

```bash
cd Base-de-Datos
pip install -r requirements.txt
```

### Base de datos

**Paso 1** — Inicializar PostgreSQL (solo la primera vez, si nunca se ha configurado):

```bash
sudo mkdir -p /var/lib/postgres
sudo chown postgres:postgres /var/lib/postgres
sudo -u postgres initdb -D /var/lib/postgres/data
sudo systemctl start postgresql
sudo systemctl enable postgresql   # para que arranque automáticamente al encender
```

> Si `initdb` dice que el directorio ya existe y no está vacío, PostgreSQL ya estaba inicializado — omite ese paso y solo ejecuta `sudo systemctl start postgresql`.

**Paso 2** — Crear la base de datos:

```bash
sudo -u postgres createdb imjuve
```

> Si `createdb` dice `database "imjuve" already exists`, la base ya existe — no hay que hacer nada más.

**Paso 3** — Coloca el dump en la ruta esperada:

```
/home/robute/Documentos/codes/IPMJ_proyect/DB_source/sistemitas.sql
```

> El archivo `DB_source/sistemitas.sql.example` contiene el schema completo sin datos. Úsalo como referencia de la estructura esperada.

### Variables de entorno (`Base-de-Datos/.env`)

```
DB_HOST=localhost
DB_PORT=5432
DB_NAME=imjuve
DB_USER=postgres
DB_PASS=tu_password

# URL del sistema de tickets Laravel (activa el botón de cambio en el sidebar)
TICKETS_URL=http://localhost:8000
```

### Arranque

```bash
cd Base-de-Datos
./start.sh
```

El script:
1. Lee `Base-de-Datos/.env`
2. Comprueba si la tabla `usuarios` existe en PostgreSQL
3. Si la base está vacía — importa `DB_source/sistemitas.sql` automáticamente
4. Detecta `streamlit` en el venv local (`.venv/`) o en el PATH
5. Lanza `streamlit run app.py`

Accede en: `http://localhost:8501`

> **Nota sobre streamlit en el PATH:** Si `start.sh` dice `streamlit: command not found`,
> el binario está en el venv local del proyecto. El script lo detecta automáticamente en
> `Base-de-Datos/.venv/bin/streamlit`. Si instalaste streamlit con pip global, asegúrate
> de que `~/.local/bin` esté en tu `$PATH`.

---

## Cambio entre sistemas

Una vez que ambos están corriendo:

- Desde **IMJTickets**: botón **Inventario** en el navbar (solo visible si `INVENTARIO_URL` está en `.env`)
- Desde **Inventario**: botón **Ir al sistema de tickets** al fondo del sidebar (solo visible si `TICKETS_URL` está en `.env`)

Ambos botones abren el otro sistema en una nueva pestaña.

---

## Problemas conocidos y soluciones

| Problema | Causa | Solución |
|---|---|---|
| `Vite manifest not found` al cargar Laravel | Assets no compilados | Ejecutar `npm run build` antes de `php artisan serve` |
| Botón de inventario no aparece en el navbar | `INVENTARIO_URL` no está en `.env` (solo en `.env.example`) | Agregar `INVENTARIO_URL=http://localhost:8501` al `.env` real y ejecutar `php artisan config:clear` |
| `streamlit: command not found` en `start.sh` | Streamlit instalado en venv local, no en PATH global | El script detecta `.venv/bin/streamlit` automáticamente; si falla, activar el venv manualmente: `source .venv/bin/activate` |
| `initdb: directory already exists` | PostgreSQL ya fue inicializado previamente | Omitir `initdb` y solo ejecutar `sudo systemctl start postgresql` |
| `database "imjuve" already exists` | La base de datos ya fue creada | No es un error — la base ya está lista |
| No puedo acceder a `/gestionar-tickets` | La ruta requiere sesión activa | Iniciar sesión primero en `http://localhost:8000` |

---

## Credenciales por defecto

| Campo | Valor |
|---|---|
| Correo | `admin@imjuventud.gob.mx` |
| Contraseña | definida al crear la cuenta (reseteable con `php artisan tinker`) |

Para resetear la contraseña desde la terminal:

```bash
php artisan tinker --execute="DB::table('users')->where('email','admin@imjuventud.gob.mx')->update(['password' => bcrypt('nueva_contraseña')]);"
```

---

## Estructura del repositorio

```
/
├── app/                        # Laravel — lógica de la aplicación
├── resources/views/            # Laravel — vistas Blade + Livewire
├── routes/                     # Laravel — rutas web
├── database/migrations/        # Laravel — migraciones
├── Base-de-Datos/
│   ├── app.py                  # Streamlit — aplicación principal (~2700 líneas)
│   ├── start.sh                # Script de arranque con auto-importación de BD
│   ├── requirements.txt        # Dependencias Python
│   └── .env                    # Credenciales PostgreSQL (no versionado)
└── DB_source/
    └── sistemitas.sql.example  # Schema PostgreSQL sin datos (referencia)
    # sistemitas.sql            # Dump real con datos institucionales (NO versionado)
```

---

## Datos sensibles — qué NO se versiona

| Archivo | Razón |
|---|---|
| `.env` / `Base-de-Datos/.env` | Credenciales de base de datos |
| `DB_source/sistemitas.sql` | Dump con datos institucionales reales |
| `Base-de-Datos/*.xlsx` / `*.csv` | Inventario con nombres y series de equipos |
| `Base-de-Datos/*.pdf` | Resguardos firmados |

Ver `.gitignore` para la lista completa.
