#!/bin/sh
set -e

if [ ! "$(ls -A /app/storage)" ]; then
  echo "Initializing storage directory..."
  cp -R /app/storage-init/. /app/storage
  chown -R www-data:www-data /app/storage
fi

rm -rf /app/storage-init

php artisan optimize:clear

exec "$@"
