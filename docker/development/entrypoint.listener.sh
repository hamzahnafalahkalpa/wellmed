#!/bin/bash
# =============================================
# Entrypoint for Listener Container
# Runs queue workers WITHOUT Octane
# For heavy jobs like tenant/db generation
# =============================================

echo "Starting Listener Container (NO OCTANE)..."
echo "This container handles heavy jobs: queue workers, tenant generation, etc."

# Create required directories
mkdir -p /app/supervisor/run /app/storage/logs /app/storage/framework/cache /app/storage/framework/sessions /app/storage/framework/views
chown -R www-data:www-data /app/supervisor/run /app/storage 2>/dev/null || true
chmod -R 775 /app/supervisor/run /app/storage 2>/dev/null || true

# Wait for dependencies
sleep 3

# Run migrations if needed (optional, usually done by main container)
# php /app/artisan migrate --force

echo "Starting Supervisor with listener-only config (no Octane)..."
exec /usr/bin/supervisord -c /etc/supervisor/supervisord-listener.conf
