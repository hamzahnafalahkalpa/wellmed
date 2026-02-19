#!/bin/bash
# Wait for RabbitMQ to be available before starting the Laravel worker
# This script prevents supervisor from constantly restarting workers when RabbitMQ is down

RABBITMQ_HOST="${RABBITMQ_HOST:-rabbitmq}"
RABBITMQ_PORT="${RABBITMQ_PORT:-5672}"
MAX_RETRIES="${RABBITMQ_MAX_RETRIES:-0}"  # 0 = infinite retries
RETRY_INTERVAL="${RABBITMQ_RETRY_INTERVAL:-10}"  # seconds between retries (increased to reduce log spam)
LOG_INTERVAL="${RABBITMQ_LOG_INTERVAL:-12}"  # only log every N attempts (12 * 10s = every 2 minutes)

retry_count=0

echo "[$(date '+%Y-%m-%d %H:%M:%S')] Waiting for RabbitMQ at $RABBITMQ_HOST:$RABBITMQ_PORT..."

while true; do
    # Check if RabbitMQ port is open using timeout and bash's /dev/tcp
    if timeout 5 bash -c "echo > /dev/tcp/$RABBITMQ_HOST/$RABBITMQ_PORT" 2>/dev/null; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] RabbitMQ is available. Starting worker..."
        break
    fi

    retry_count=$((retry_count + 1))

    if [ "$MAX_RETRIES" -gt 0 ] && [ "$retry_count" -ge "$MAX_RETRIES" ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] ERROR: RabbitMQ not available after $MAX_RETRIES attempts. Exiting."
        exit 1
    fi

    # Only log every LOG_INTERVAL attempts to prevent log spam
    if [ $((retry_count % LOG_INTERVAL)) -eq 1 ]; then
        echo "[$(date '+%Y-%m-%d %H:%M:%S')] RabbitMQ not available (attempt $retry_count). Retrying every ${RETRY_INTERVAL}s..."
    fi

    sleep "$RETRY_INTERVAL"
done

# Execute the actual command passed as arguments
exec "$@"
