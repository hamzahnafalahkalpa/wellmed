#!/bin/sh
set -e


exec "$@"


#!/bin/bash
set -e

echo "==> Entrypoint hq running..."

# # masuk ke root project
# cd /app/projects/hq

# composer install untuk root project
# echo "==> Running composer install in /app..."
# cd /app
# rm -f /composer.lock
# composer install --no-interaction --prefer-dist --optimize-autoloader

# kalau ada migrasi, cache, dll bisa ditambah
# php artisan migrate --force
# php artisan config:cache
# php artisan route:cache

# terakhir jalankan php-fpm
exec "$@"
