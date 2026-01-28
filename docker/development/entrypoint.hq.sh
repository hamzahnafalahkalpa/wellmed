#!/bin/bash
set -e

echo "==> Entrypoint HQ running..."

# -------------------------
# Load .env if exists
# -------------------------
ENV_FILE="/app/.env"
if [ -f "$ENV_FILE" ]; then
    echo "==> Loading environment from $ENV_FILE"
    export $(grep -v '^#' "$ENV_FILE" | xargs)
fi

echo "APP_ENV=${APP_ENV:-development}"

# -------------------------
# Default Octane config (no staging check)
# -------------------------
export OCTANE_PORT=${OCTANE_PORT:-9001}
export OCTANE_ADMIN_PORT=${OCTANE_ADMIN_PORT:-9006}
export OCTANE_WORKERS=${OCTANE_WORKERS:-4}

echo "==> OCTANE_PORT=$OCTANE_PORT"
echo "==> OCTANE_ADMIN_PORT=$OCTANE_ADMIN_PORT"
echo "==> OCTANE_WORKERS=$OCTANE_WORKERS"

# -------------------------
# Ensure supervisord dir exists
# -------------------------
mkdir -p /app/supervisor/run
chown -R www-data:www-data /app/supervisor

# -------------------------
# Start supervisord as PID 1
# -------------------------
echo "==> Starting supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
