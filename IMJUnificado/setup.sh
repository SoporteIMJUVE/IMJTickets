#!/usr/bin/env bash
# setup.sh — configura el proyecto desde cero en una sola corrida
# Uso: bash setup.sh

set -e  # Detener si cualquier comando falla

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║         IMJUnificado — Setup inicial             ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""

# 1. Dependencias PHP
echo "▶ Instalando dependencias PHP (composer)..."
composer install --no-interaction

# 2. Entorno
if [ ! -f .env ]; then
    echo "▶ Creando .env desde .env.example..."
    cp .env.example .env
    php artisan key:generate
else
    echo "✓ .env ya existe, omitiendo."
fi

# 3. Base de datos SQLite local
if [ ! -f database/database.sqlite ]; then
    echo "▶ Creando base de datos SQLite..."
    touch database/database.sqlite
fi

# 4. Migraciones y datos semilla
echo "▶ Corriendo migraciones y seeders..."
php artisan migrate:fresh --seed --force

# 5. Frontend
echo "▶ Instalando dependencias Node.js..."
npm install

echo "▶ Compilando CSS y JS..."
npm run build

echo ""
echo "╔══════════════════════════════════════════════════╗"
echo "║             ¡Setup completo!                     ║"
echo "║                                                  ║"
echo "║  Para iniciar el servidor:                       ║"
echo "║    composer dev                                  ║"
echo "║                                                  ║"
echo "║  O solo PHP:                                     ║"
echo "║    php artisan serve                             ║"
echo "║                                                  ║"
echo "║  Login:                                          ║"
echo "║    admin@imjuventud.gob.mx / admin123            ║"
echo "╚══════════════════════════════════════════════════╝"
echo ""
