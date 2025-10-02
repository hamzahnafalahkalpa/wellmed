#!/bin/sh
set -e


exec "$@"


#!/bin/bash
set -e

echo "==> Entrypoint Lite running..."

# # masuk ke root project
cd /app/projects/hq

# # composer install untuk root project
echo "==> Running composer install in /app..."
composer install

# kalau ada migrasi, cache, dll bisa ditambah
# php artisan migrate --force
# php artisan config:cache
# php artisan route:cache

# terakhir jalankan php-fpm
exec "$@"
