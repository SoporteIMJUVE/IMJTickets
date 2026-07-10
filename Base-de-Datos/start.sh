#!/usr/bin/env bash
# Arranca el sistema de inventario IMJUVE (Streamlit + PostgreSQL)
# Si la base de datos está vacía, importa el respaldo antes de levantar la app.

set -e

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="$SCRIPT_DIR/.env"
DUMP_PATH="/home/robute/Documentos/codes/IPMJ_proyect/DB_source/sistemitas.sql"

# Leer variables del .env
if [ -f "$ENV_FILE" ]; then
    export $(grep -v '^#' "$ENV_FILE" | grep -v '^$' | xargs)
fi

DB_HOST="${DB_HOST:-localhost}"
DB_PORT="${DB_PORT:-5432}"
DB_NAME="${DB_NAME:-imjuve}"
DB_USER="${DB_USER:-postgres}"
export PGPASSWORD="${DB_PASS:-}"

echo ">>> Verificando base de datos '$DB_NAME' en $DB_HOST:$DB_PORT..."

# Comprobar si la tabla usuarios existe y tiene registros
TIENE_DATOS=$(psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -tAc \
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public' AND table_name='usuarios';" \
    2>/dev/null || echo "0")

if [ "$TIENE_DATOS" = "0" ]; then
    echo ">>> Base de datos vacía. Importando respaldo desde:"
    echo "    $DUMP_PATH"

    if [ ! -f "$DUMP_PATH" ]; then
        echo "ERROR: No se encontró el archivo $DUMP_PATH"
        echo "       Coloca el dump ahí o ajusta la ruta en start.sh"
        exit 1
    fi

    psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -f "$DUMP_PATH"
    echo ">>> Importación completada."
else
    echo ">>> Base de datos con datos existentes, omitiendo importación."
fi

echo ">>> Iniciando Streamlit..."
cd "$SCRIPT_DIR"

# Buscar streamlit en el venv local, luego en el PATH
STREAMLIT_BIN=""
for candidate in "$SCRIPT_DIR/.venv/bin/streamlit" "$SCRIPT_DIR/venv/bin/streamlit" "$(which streamlit 2>/dev/null)"; do
    if [ -x "$candidate" ]; then
        STREAMLIT_BIN="$candidate"
        break
    fi
done

if [ -z "$STREAMLIT_BIN" ]; then
    echo "ERROR: No se encontró streamlit. Activa el venv o instala con: pip install streamlit"
    exit 1
fi

"$STREAMLIT_BIN" run app.py
