#!/bin/bash
# =============================================
# Entrypoint for Listener Container (PRODUCTION)
# Runs queue workers WITHOUT Octane
# For heavy jobs like tenant/db generation
# =============================================

echo "Starting Listener Container (NO OCTANE) - PRODUCTION..."
echo "This container handles heavy jobs: queue workers, tenant generation, etc."

# Create required directories
mkdir -p /app/supervisor/run /app/storage/logs /app/storage/framework/cache /app/storage/framework/sessions /app/storage/framework/views
chown -R www-data:www-data /app/supervisor/run /app/storage 2>/dev/null || true
chmod -R 775 /app/supervisor/run /app/storage 2>/dev/null || true

# Copy storage if empty (production)
if [ -d "/app/storage-init" ] && [ ! -f "/app/storage/.initialized" ]; then
    echo "Initializing storage directory..."
    cp -rn /app/storage-init/* /app/storage/ 2>/dev/null || true
    touch /app/storage/.initialized
    chown -R www-data:www-data /app/storage
fi

# Wait for dependencies
sleep 3

# Clear and cache config for production
php /app/artisan config:cache 2>/dev/null || true

echo "Starting Supervisor with listener-only config (no Octane)..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord-listener.conf
