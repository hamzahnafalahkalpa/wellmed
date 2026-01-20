#!/bin/bash
set -e

echo "==> Entrypoint Backbone running..."

# -------------------------
# Load .env if exists
# -------------------------
ENV_FILE="/app/.env"
if [ -f "$ENV_FILE" ]; then
    echo "==> Loading environment from $ENV_FILE"
    export $(grep -v '^#' "$ENV_FILE" | xargs)
fi

echo "APP_ENV = ${APP_ENV:-development}"

# -------------------------
# Opcache setup (CLI untuk Octane)
# -------------------------
# PHP_OPCACHE_CONF=/usr/local/etc/php/conf.d/00-opcache.ini

# if [ ! -f "$PHP_OPCACHE_CONF" ]; then
#   echo "==> Creating Opcache CLI configuration..."
#   cat <<EOL > $PHP_OPCACHE_CONF
# opcache.enable=1
# opcache.enable_cli=1
# opcache.memory_consumption=256
# opcache.interned_strings_buffer=16
# opcache.max_accelerated_files=10000
# opcache.validate_timestamps=0
# opcache.revalidate_freq=0
# EOL
# fi

# # -------------------------
# # Upload size config
# # -------------------------
# echo "upload_max_filesize=10M" > /usr/local/etc/php/conf.d/99-upload.ini
# echo "post_max_size=10M" >> /usr/local/etc/php/conf.d/99-upload.ini

# -------------------------
# ENV-based config with fallback
# -------------------------
if [ "${APP_ENV}" = "staging" ]; then
    APP_PORT=${APP_PORT:-9011}
    ADMIN_PORT=${ADMIN_PORT:-9012}
    WORKERS=${WORKERS:-8}
    echo "==> Environment: STAGING"
else
    APP_PORT=${APP_PORT:-9000}
    ADMIN_PORT=${ADMIN_PORT:-9005}
    WORKERS=${WORKERS:-4}
    echo "==> Environment: DEVELOPMENT"
fi

echo "==> PORT: $APP_PORT"
echo "==> ADMIN PORT: $ADMIN_PORT"
echo "==> WORKERS: $WORKERS"

# -------------------------
# Jalankan Laravel Octane (FrankenPHP)
# -------------------------
echo "==> Starting Laravel Octane..."
exec php \
    -d upload_max_filesize=10M \
    -d post_max_size=10M \
    artisan octane:frankenphp \
    --host=0.0.0.0 \
    --port=${APP_PORT} \
    --admin-port=${ADMIN_PORT} \
    --workers=${WORKERS} \
    --max-requests=1000 \
    --watch
