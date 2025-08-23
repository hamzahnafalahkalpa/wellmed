#!/bin/sh
set -e

echo "🚀 Starting Klinik Franken entrypoint..."

# --- Fix permissions ---
echo "🔧 Fixing file permissions..."
chown -R ${UID:-1000}:${GID:-1000} /app/repositories /app/app /app/projects/klinik /app/groups /app/tenants /app/public /app/storage || true
chmod -R 775 /app/storage /app/bootstrap/cache || true

# --- Clear Laravel caches ---
echo "🧹 Clearing Laravel caches..."
php /app/artisan optimize:clear

# --- Start FrankenPHP server ---
echo "🚀 Serving FrankenPHP on port ${FRANKEN_PORT:-8004}..."
exec frankenphp start --host=0.0.0.0 --port=${FRANKEN_PORT:-8004} --workers=${PHP_CLI_SERVER_WORKERS:-4} --watch /app
