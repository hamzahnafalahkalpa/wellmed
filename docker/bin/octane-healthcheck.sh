#!/bin/bash
# Octane Health Check Script
# This script monitors Octane memory usage and restarts if necessary

MAX_MEMORY_MB=${OCTANE_MAX_MEMORY:-2048}  # 2GB default
CHECK_INTERVAL=${OCTANE_CHECK_INTERVAL:-60}  # 60 seconds default
LOG_FILE="/app/supervisor/run/octane-healthcheck.log"
STALE_STATE_THRESHOLD=${OCTANE_STALE_THRESHOLD:-300}  # 5 minutes default
CONSECUTIVE_FAILURES=0
MAX_CONSECUTIVE_FAILURES=3

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

get_octane_memory() {
    # Get memory usage of Octane/FrankenPHP process in MB
    local mem=$(ps aux | grep -E "frankenphp|php.*octane" | grep -v grep | awk '{sum+=$6} END {print int(sum/1024)}')
    echo "${mem:-0}"
}

check_heartbeat() {
    # Check multiple possible state file locations
    local state_files=(
        "/app/storage/logs/octane-server-state.json"
        "/app/storage/logs/octane-server-state-backbone.json"
        "/app/storage/logs/octane-server-state-lite.json"
    )

    for STATE_FILE in "${state_files[@]}"; do
        if [ -f "$STATE_FILE" ]; then
            local FILE_AGE=$(($(date +%s) - $(stat -c %Y "$STATE_FILE" 2>/dev/null || echo "0")))
            if [ $FILE_AGE -lt $STALE_STATE_THRESHOLD ]; then
                return 0  # Found a recent state file
            fi
        fi
    done

    # No recent state file found - but this might be okay if Octane just started
    return 1
}

check_process() {
    # Check if Octane/FrankenPHP process is running and responsive
    if ! pgrep -f "frankenphp" > /dev/null 2>&1; then
        if ! pgrep -f "octane:start" > /dev/null 2>&1; then
            log "ERROR: Octane/FrankenPHP process not found"
            return 1
        fi
    fi
    return 0
}

check_http_health() {
    # Quick HTTP health check (timeout 5 seconds)
    local port=${OCTANE_PORT:-9000}
    if command -v curl &> /dev/null; then
        if curl -sf --max-time 5 "http://127.0.0.1:${port}/health" > /dev/null 2>&1; then
            return 0
        fi
        # Try basic connectivity
        if curl -sf --max-time 5 -o /dev/null "http://127.0.0.1:${port}/" 2>&1; then
            return 0
        fi
    fi
    return 1
}

graceful_restart_octane() {
    log "Attempting graceful restart of Octane..."
    # Send SIGUSR1 for graceful reload first
    pkill -USR1 -f "frankenphp" 2>/dev/null || true
    sleep 5

    # If still having issues, do full restart
    if ! check_http_health; then
        log "Graceful reload failed, doing full restart..."
        supervisorctl restart backbone-octane 2>&1 | tee -a "$LOG_FILE" || \
        supervisorctl restart laravel-octane 2>&1 | tee -a "$LOG_FILE"
    fi
}

restart_octane() {
    log "Restarting Octane via supervisorctl..."
    supervisorctl restart backbone-octane 2>&1 | tee -a "$LOG_FILE" || \
    supervisorctl restart laravel-octane 2>&1 | tee -a "$LOG_FILE"
    sleep 10
    CONSECUTIVE_FAILURES=0
}

log "Starting Octane health monitor"
log "Config: Max Memory=${MAX_MEMORY_MB}MB, Interval=${CHECK_INTERVAL}s, Stale Threshold=${STALE_STATE_THRESHOLD}s"

# Initial delay to allow Octane to start
sleep 30

while true; do
    sleep "$CHECK_INTERVAL"

    # Check if process is running
    if ! check_process; then
        log "Octane process check failed - supervisor should auto-restart"
        CONSECUTIVE_FAILURES=$((CONSECUTIVE_FAILURES + 1))
        continue
    fi

    # Check memory usage
    CURRENT_MEMORY=$(get_octane_memory)

    if [ -n "$CURRENT_MEMORY" ] && [ "$CURRENT_MEMORY" -gt 0 ]; then
        # Only log every 5 checks to reduce noise
        if [ $((RANDOM % 5)) -eq 0 ]; then
            log "Memory: ${CURRENT_MEMORY}MB / ${MAX_MEMORY_MB}MB"
        fi

        if [ "$CURRENT_MEMORY" -gt "$MAX_MEMORY_MB" ]; then
            log "CRITICAL: Memory limit exceeded (${CURRENT_MEMORY}MB > ${MAX_MEMORY_MB}MB)! Restarting..."
            restart_octane
            continue
        fi
    fi

    # Check HTTP health
    if ! check_http_health; then
        CONSECUTIVE_FAILURES=$((CONSECUTIVE_FAILURES + 1))
        log "WARNING: HTTP health check failed (${CONSECUTIVE_FAILURES}/${MAX_CONSECUTIVE_FAILURES})"

        if [ $CONSECUTIVE_FAILURES -ge $MAX_CONSECUTIVE_FAILURES ]; then
            log "CRITICAL: ${MAX_CONSECUTIVE_FAILURES} consecutive health check failures! Restarting..."
            restart_octane
        fi
    else
        # Reset failure counter on success
        if [ $CONSECUTIVE_FAILURES -gt 0 ]; then
            log "Health check recovered after ${CONSECUTIVE_FAILURES} failures"
            CONSECUTIVE_FAILURES=0
        fi
    fi
done
