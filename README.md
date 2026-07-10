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

1. Crea la base de datos en PostgreSQL:

```sql
CREATE DATABASE imjuve;
```

2. Coloca el dump en la ruta esperada:

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
3. Si la base está vacía — importa `DB_source/sistemitas.sql`
4. Lanza `streamlit run app.py`

Accede en: `http://localhost:8501`

---

## Cambio entre sistemas

Una vez que ambos están corriendo:

- Desde **IMJTickets**: botón **Inventario** en el navbar (solo visible si `INVENTARIO_URL` está configurado)
- Desde **Inventario**: botón **Ir al sistema de tickets** al fondo del sidebar (solo visible si `TICKETS_URL` está configurado)

Ambos botones abren el otro sistema en una nueva pestaña.

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
