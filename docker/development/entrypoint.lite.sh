#!/bin/bash
set -e

echo "==> Entrypoint Lite running..."

# -------------------------
# Opcache setup (CLI untuk Octane)
# -------------------------
PHP_OPCACHE_CONF=/usr/local/etc/php/conf.d/00-opcache.ini

if [ ! -f "$PHP_OPCACHE_CONF" ]; then
  echo "==> Creating Opcache CLI configuration..."
  cat <<EOL > $PHP_OPCACHE_CONF
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.revalidate_freq=0
EOL
fi

# -------------------------
# Optional: composer install / migrate / cache
# -------------------------
# cd /app/projects/wellmed-lite
# echo "==> Installing dependencies..."
# composer install --no-interaction --prefer-dist --optimize-autoloader

# php artisan migrate --force
# php artisan config:cache
# php artisan route:cache
# php artisan view:cache

# -------------------------
# Jalankan Laravel Octane (FrankenPHP)
# -------------------------
echo "==> Starting Laravel Octane..."
exec php artisan octane:frankenphp \
    --host=0.0.0.0 \
    --port=9000 \
    --admin-port=9005 \
    --workers=4 \
    --max-requests=1000 \
    --server=frankenphp
