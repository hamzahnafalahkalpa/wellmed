#!/bin/bash
set -e

echo "==> Entrypoint Lite running..."

# masuk ke root project
# cd /app/projects/wellmed-lite
# rm -f /composer.lock

# # composer install untuk root project
# echo "==> Running composer install in /app..."
# composer install
# cd /app

# rm -f /composer.lock
# composer install --no-interaction --prefer-dist --optimize-autoloader

# kalau ada migrasi, cache, dll bisa ditambah

# terakhir jalankan php-fpm
exec "$@"
