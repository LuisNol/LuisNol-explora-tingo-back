#!/bin/bash
set -e

echo "=== Despliegue de ExploreTingo Backend ==="

if [ "$APP_ENV" != "production" ]; then
    echo "Advertencia: APP_ENV no es 'production'. Editá tu .env"
    exit 1
fi

echo "1. Construyendo imagen de produccion..."
docker compose -f docker-compose.prod.yml build

echo "2. Deteniendo servicios anteriores..."
docker compose -f docker-compose.prod.yml down

echo "3. Iniciando servicios..."
docker compose -f docker-compose.prod.yml up -d

echo "4. Ejecutando migraciones..."
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

echo "5. Ejecutando seeders..."
docker compose -f docker-compose.prod.yml exec app php artisan db:seed --force

echo "6. Optimizando aplicacion..."
docker compose -f docker-compose.prod.yml exec app php artisan optimize

echo "=== Despliegue completado ==="
echo "Accede a: http://localhost:8000"
