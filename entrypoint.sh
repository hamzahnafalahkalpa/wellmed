#!/bin/bash
echo "Setting permissions..."
mkdir -p /app/storage/framework/views
chmod -R 775 /app/storage /app/bootstrap/cache /app/public || true

# Jalankan perintah utama
php artisan package:discover --ansi

exec "$@"
