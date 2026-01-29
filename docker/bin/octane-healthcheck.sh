#!/bin/bash
# Octane Health Check Script
# This script monitors Octane memory usage and restarts if necessary

MAX_MEMORY_MB=${OCTANE_MAX_MEMORY:-2048}  # 2GB default
CHECK_INTERVAL=${OCTANE_CHECK_INTERVAL:-60}  # 60 seconds default
LOG_FILE="/app/supervisor/run/octane-healthcheck.log"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

get_octane_memory() {
    # Get memory usage of Octane/FrankenPHP process in MB
    ps aux | grep -E "octane:start|frankenphp" | grep -v grep | awk '{sum+=$6} END {print int(sum/1024)}'
}

check_heartbeat() {
    # Check if Octane state file exists and is recent (within last 2 minutes)
    STATE_FILE="/app/storage/logs/octane-server-state.json"
    if [ -f "$STATE_FILE" ]; then
        FILE_AGE=$(($(date +%s) - $(stat -c %Y "$STATE_FILE")))
        if [ $FILE_AGE -gt 120 ]; then
            log "WARNING: Octane state file is stale (${FILE_AGE}s old)"
            return 1
        fi
    fi
    return 0
}

check_process() {
    # Check if Octane process is running
    if ! pgrep -f "octane:start" > /dev/null; then
        log "ERROR: Octane process not found"
        return 1
    fi
    return 0
}

restart_octane() {
    log "Restarting Octane via supervisorctl..."
    supervisorctl restart backbone-octane 2>&1 | tee -a "$LOG_FILE" || \
    supervisorctl restart laravel-octane 2>&1 | tee -a "$LOG_FILE"
}

log "Starting Octane health monitor (Max Memory: ${MAX_MEMORY_MB}MB, Interval: ${CHECK_INTERVAL}s)"

while true; do
    sleep "$CHECK_INTERVAL"

    # Check if process is running
    if ! check_process; then
        log "Octane process check failed"
        continue
    fi

    # Check memory usage
    CURRENT_MEMORY=$(get_octane_memory)

    if [ -n "$CURRENT_MEMORY" ]; then
        log "Memory usage: ${CURRENT_MEMORY}MB / ${MAX_MEMORY_MB}MB"

        if [ "$CURRENT_MEMORY" -gt "$MAX_MEMORY_MB" ]; then
            log "CRITICAL: Memory limit exceeded! Restarting Octane..."
            restart_octane
            sleep 10
        fi
    fi

    # Check heartbeat
    if ! check_heartbeat; then
        log "WARNING: Heartbeat check failed"
    fi
done
