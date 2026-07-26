#!/bin/sh
set -e

if [ "$APP_ENV" = "production" ]; then
    echo "Running production optimizations..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

if [ ! -d "storage/framework/views" ]; then
    mkdir -p storage/framework/views
fi

if [ ! -d "storage/framework/cache" ]; then
    mkdir -p storage/framework/cache
fi

if [ ! -d "storage/framework/sessions" ]; then
    mkdir -p storage/framework/sessions
fi

chown -R www-data:www-data storage bootstrap/cache

if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi

if [ ! -L "public/storage" ]; then
    php artisan storage:link
fi

exec "$@"
