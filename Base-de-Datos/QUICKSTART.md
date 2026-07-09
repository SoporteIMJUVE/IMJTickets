# Quickstart — Sistema Inventario IMJUVE (Python + Streamlit)

---

## Requisitos

| Herramienta | Verificar con |
|---|---|
| Python 3.10+ | `python --version` |
| PostgreSQL 14+ | `psql --version` |
| pip | `pip --version` |

---

## 1. Entorno virtual

Desde la carpeta `Base-de-Datos/`:

```bash
cd Base-de-Datos

# Crear entorno virtual (solo la primera vez)
python -m venv .venv

# Activar
source .venv/bin/activate        # Linux / Mac
# .venv\Scripts\activate         # Windows

# Instalar dependencias
pip install -r requirements.txt
```

Para saber si el entorno está activo: el prompt muestra `(.venv)` al inicio.

Para desactivarlo cuando termines:
```bash
deactivate
```

---

## 2. Base de datos PostgreSQL

### Crear la base de datos

```bash
psql -U postgres -c "CREATE DATABASE sistemitas;"
```

> Si tu usuario de PostgreSQL no es `postgres`, cámbialo por el tuyo. En Manjaro/Arch suele ser tu usuario del sistema.

### Restaurar el respaldo

El dump está en `DB_source/` (un nivel arriba de `Base-de-Datos/`):

```bash
psql -U postgres -d sistemitas \
  -f /home/robute/Documentos/codes/IPMJ_proyect/DB_source/sistemitas.sql
```

Esto recrea todas las tablas y carga los datos. Tarda ~30 segundos.

---

## 3. Archivo de entorno (.env)

El archivo `.env` ya existe en esta carpeta. Verifica que apunte a la base de datos correcta:

```env
DB_HOST=localhost
DB_PORT=5432
DB_NAME=sistemitas
DB_USER=postgres
DB_PASS=
```

Edítalo si tu usuario o contraseña de PostgreSQL son distintos:

```bash
nano .env
```

---

## 4. Ejecutar la aplicación

Con el entorno virtual activo:

```bash
streamlit run app.py
```

Abre **http://localhost:8501**

Para usar un puerto distinto:
```bash
streamlit run app.py --server.port 8502
```

---

## Arranque rápido (después del setup inicial)

```bash
cd Base-de-Datos
source .venv/bin/activate
streamlit run app.py
```

---

## Errores comunes

**`psycopg2.OperationalError: could not connect to server`**

PostgreSQL no está corriendo. Inícialo:
```bash
sudo systemctl start postgresql
```

Para que arranque automáticamente:
```bash
sudo systemctl enable postgresql
```

---

**`ModuleNotFoundError`**

El entorno virtual no está activo. Ejecuta:
```bash
source .venv/bin/activate
pip install -r requirements.txt
```

---

**`role "postgres" does not exist`**

En Manjaro/Arch, el superusuario de PostgreSQL es tu usuario del sistema, no `postgres`. Usa:
```bash
psql -U $USER -c "CREATE DATABASE sistemitas;"
psql -U $USER -d sistemitas -f /home/robute/Documentos/codes/IPMJ_proyect/DB_source/sistemitas.sql
```

Y en `.env`:
```env
DB_USER=tu_usuario
DB_PASS=
```

---

**`database "sistemitas" already exists`**

Puedes ignorar ese error, o borrarla y recrearla:
```bash
psql -U postgres -c "DROP DATABASE sistemitas;"
psql -U postgres -c "CREATE DATABASE sistemitas;"
psql -U postgres -d sistemitas -f /home/robute/.../sistemitas.sql
```
