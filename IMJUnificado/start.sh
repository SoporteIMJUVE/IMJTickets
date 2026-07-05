#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────
# start.sh — Script de arranque IMJUnificado
#
# Uso:
#   ./start.sh              arranque normal (detecta si es el primero)
#   ./start.sh --force      fuerza reimportación de datos legados
#   ./start.sh --port 8001  cambia el puerto (default: 8000)
#
# El script:
#  1. Verifica que las migraciones estén al día
#  2. Corre php artisan app:boot (importa legado solo en primer arranque)
#  3. Levanta php artisan serve
# ─────────────────────────────────────────────────────────────────

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

PORT=8000
FORCE=""

# Parsear argumentos
while [[ $# -gt 0 ]]; do
    case $1 in
        --port)   PORT="$2"; shift 2 ;;
        --force)  FORCE="--force";   shift ;;
        *)        shift ;;
    esac
done

echo ""
echo "  ┌─────────────────────────────────────────────┐"
echo "  │  IMJUnificado — Instituto Mexicano de la    │"
echo "  │  Juventud · Sistema Integral TI             │"
echo "  └─────────────────────────────────────────────┘"
echo ""

# ── 1. Verificar .env ────────────────────────────────────────────
if [ ! -f ".env" ]; then
    echo "  ERROR: No se encontró el archivo .env"
    echo "  Copia .env.example a .env y configura la base de datos."
    exit 1
fi

# ── 2. Migraciones ───────────────────────────────────────────────
echo "  Verificando migraciones..."
php artisan migrate --force --no-interaction 2>&1 | sed 's/^/  /'

# ── 3. Primer arranque / importación legado ──────────────────────
php artisan app:boot $FORCE

# ── 4. Limpiar caché de rutas y config ──────────────────────────
php artisan config:cache  --quiet
php artisan route:cache   --quiet

# ── 5. Levantar servidor ─────────────────────────────────────────
echo ""
echo "  Iniciando servidor en http://localhost:${PORT}"
echo "  Presiona Ctrl+C para detener."
echo ""

php artisan serve --host=0.0.0.0 --port="$PORT"
