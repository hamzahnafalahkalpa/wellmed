#!/bin/sh
set -e

USER_ID=${UID:-1000}
GROUP_ID=${GID:-1000}

echo "Fixing file permissions..."
chown -R ${USER_ID}:${GROUP_ID} /app/storage /app/bootstrap/cache /app/public/frankenphp-worker.php || echo "Some files could not be changed"

echo "Clearing caches..."
php artisan optimize:clear

exec "$@"
