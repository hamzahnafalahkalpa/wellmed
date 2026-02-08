#!/bin/bash
set -e

echo "==> Entrypoint Backbone (Production) running..."

# load env
ENV_FILE="/app/.env"
if [ -f "$ENV_FILE" ]; then
    set -a  # automatically export all variables
    source "$ENV_FILE"
    set +a
fi

# Set defaults for Octane if not defined
export OCTANE_PORT="${OCTANE_PORT:-9000}"
export OCTANE_WORKERS="${OCTANE_WORKERS:-4}"
export OCTANE_MAX_REQUESTS="${OCTANE_MAX_REQUESTS:-500}"
export OCTANE_MAX_MEMORY="${OCTANE_MAX_MEMORY:-2048}"
export OCTANE_CHECK_INTERVAL="${OCTANE_CHECK_INTERVAL:-60}"

echo "==> Octane Config: Port=${OCTANE_PORT}, Workers=${OCTANE_WORKERS}, MaxRequests=${OCTANE_MAX_REQUESTS}"

# ensure supervisor dir exists
mkdir -p /app/supervisor/run
chown -R www-data:www-data /app/supervisor

# Clear any stale Octane state files
rm -f /app/storage/logs/octane-server-state*.json 2>/dev/null || true

# Optimize for production
# Note: config:cache may fail with Octane due to spread operators calling static methods
# route:cache may fail if duplicate route names exist - these are non-fatal
echo "==> Optimizing for production..."
if php artisan config:cache 2>&1; then
    echo "    Config cached successfully"
else
    echo "    Warning: config:cache failed (non-fatal, app will work without cache)"
fi

if php artisan route:cache 2>&1; then
    echo "    Routes cached successfully"
else
    echo "    Warning: route:cache failed (non-fatal, app will work without cache)"
fi

echo "==> Starting supervisord (PID 1)"
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
