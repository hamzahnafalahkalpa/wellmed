#!/bin/bash
set -e

echo "==> Entrypoint Hq running..."

# load env
ENV_FILE="/app/.env"
if [ -f "$ENV_FILE" ]; then
    export $(grep -v '^#' "$ENV_FILE" | xargs)
fi

# ensure supervisor dir exists
mkdir -p /app/supervisor/run
chown -R www-data:www-data /app/supervisor

echo "==> Starting supervisord (PID 1)"
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
