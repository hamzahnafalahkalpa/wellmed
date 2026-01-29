#!/bin/bash

# PgBouncer Connection Pool Monitoring Script
# Monitors connection pool status, statistics, and alerts on thresholds

set -e

# Configuration
PGHOST="${PGHOST:-localhost}"
PGPORT="${PGPORT:-6432}"
PGUSER="${PGUSER:-postgres}"
PGPASSWORD="${PGPASSWORD:-password123}"

# Alert thresholds
SATURATION_WARNING=70  # Warn at 70% pool saturation
SATURATION_CRITICAL=85  # Critical at 85% pool saturation
MAX_WAIT_WARNING=5     # Warn if clients waiting > 5 seconds
WAITING_CLIENTS_WARNING=10  # Warn if > 10 clients waiting

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Timestamp
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

echo "============================================================"
echo "  PgBouncer Connection Pool Monitor"
echo "  Timestamp: $TIMESTAMP"
echo "============================================================"
echo ""

# Connection Pool Status
echo -e "${BLUE}Connection Pool Status${NC}"
echo "-----------------------------------------------------------"
PGPASSWORD=$PGPASSWORD psql -h $PGHOST -p $PGPORT -U $PGUSER -d pgbouncer -c "
SELECT
    database,
    cl_active as active_clients,
    cl_waiting as waiting_clients,
    sv_active as active_servers,
    sv_idle as idle_servers,
    sv_used as used_servers,
    sv_tested as tested_servers,
    maxwait,
    pool_mode
FROM pgbouncer.pools
WHERE database != 'pgbouncer'
ORDER BY cl_active DESC;
" 2>/dev/null || echo "Failed to retrieve pool status"
echo ""

# Connection Statistics
echo -e "${BLUE}Connection Statistics${NC}"
echo "-----------------------------------------------------------"
PGPASSWORD=$PGPASSWORD psql -h $PGHOST -p $PGPORT -U $PGUSER -d pgbouncer -c "
SELECT
    database,
    total_xact_count as total_transactions,
    total_query_count as total_queries,
    ROUND(total_received / 1024 / 1024, 2) as received_mb,
    ROUND(total_sent / 1024 / 1024, 2) as sent_mb,
    ROUND(total_xact_time::numeric / 1000000, 2) as total_xact_time_sec,
    ROUND((total_xact_time::numeric / NULLIF(total_xact_count, 0)) / 1000, 2) as avg_xact_time_ms,
    ROUND((total_query_time::numeric / NULLIF(total_query_count, 0)) / 1000, 2) as avg_query_time_ms
FROM pgbouncer.stats
WHERE database != 'pgbouncer'
ORDER BY total_transactions DESC
LIMIT 10;
" 2>/dev/null || echo "Failed to retrieve statistics"
echo ""

# Active Connections (top 10)
echo -e "${BLUE}Active Client Connections (Top 10)${NC}"
echo "-----------------------------------------------------------"
PGPASSWORD=$PGPASSWORD psql -h $PGHOST -p $PGPORT -U $PGUSER -d pgbouncer -c "
SELECT
    database,
    user,
    state,
    addr,
    port,
    connect_time,
    request_time,
    wait,
    ptr,
    link
FROM pgbouncer.clients
WHERE database != 'pgbouncer'
ORDER BY connect_time DESC
LIMIT 10;
" 2>/dev/null || echo "Failed to retrieve active connections"
echo ""

# Server Connections
echo -e "${BLUE}Server Connections${NC}"
echo "-----------------------------------------------------------"
PGPASSWORD=$PGPASSWORD psql -h $PGHOST -p $PGPORT -U $PGUSER -d pgbouncer -c "
SELECT
    database,
    user,
    state,
    addr,
    port,
    connect_time,
    request_time,
    ptr,
    link,
    remote_pid
FROM pgbouncer.servers
WHERE database != 'pgbouncer'
ORDER BY connect_time DESC
LIMIT 10;
" 2>/dev/null || echo "Failed to retrieve server connections"
echo ""

# Health Checks and Alerts
echo -e "${BLUE}Health Checks & Alerts${NC}"
echo "-----------------------------------------------------------"

# Get pool configuration
MAX_CLIENT_CONN=$(PGPASSWORD=$PGPASSWORD psql -h $PGHOST -p $PGPORT -U $PGUSER -d pgbouncer -t -A -c "
SHOW CONFIG;
" 2>/dev/null | grep "^max_client_conn" | cut -d'|' -f2 || echo "1000")

# Check connection saturation for main database
POOL_DATA=$(PGPASSWORD=$PGPASSWORD psql -h $PGHOST -p $PGPORT -U $PGUSER -d pgbouncer -t -A -c "
SELECT cl_active, cl_waiting, maxwait
FROM pgbouncer.pools
WHERE database = 'wellmed'
LIMIT 1;
" 2>/dev/null)

if [ -n "$POOL_DATA" ]; then
    CL_ACTIVE=$(echo "$POOL_DATA" | cut -d'|' -f1)
    CL_WAITING=$(echo "$POOL_DATA" | cut -d'|' -f2)
    MAX_WAIT=$(echo "$POOL_DATA" | cut -d'|' -f3)

    # Calculate saturation percentage
    SATURATION=$(awk "BEGIN {printf \"%.1f\", ($CL_ACTIVE / $MAX_CLIENT_CONN) * 100}")

    echo "Active Clients: $CL_ACTIVE / $MAX_CLIENT_CONN"
    echo "Saturation: $SATURATION%"

    # Saturation alerts
    if (( $(echo "$SATURATION >= $SATURATION_CRITICAL" | bc -l) )); then
        echo -e "${RED}🔴 CRITICAL${NC}: Connection pool saturation at ${SATURATION}% (threshold: ${SATURATION_CRITICAL}%)"
        echo "  Action Required: Increase pool size or investigate connection leaks"
    elif (( $(echo "$SATURATION >= $SATURATION_WARNING" | bc -l) )); then
        echo -e "${YELLOW}⚠️  WARNING${NC}: Connection pool saturation at ${SATURATION}% (threshold: ${SATURATION_WARNING}%)"
        echo "  Action Recommended: Monitor closely, consider scaling"
    else
        echo -e "${GREEN}✓ OK${NC}: Connection pool healthy (${SATURATION}% utilization)"
    fi

    # Waiting clients alert
    if [ "$CL_WAITING" -gt "$WAITING_CLIENTS_WARNING" ]; then
        echo -e "${YELLOW}⚠️  WARNING${NC}: $CL_WAITING clients waiting for connections (threshold: $WAITING_CLIENTS_WARNING)"
        echo "  Action Required: Investigate slow queries or increase pool size"
    elif [ "$CL_WAITING" -gt 0 ]; then
        echo -e "${YELLOW}ℹ️  INFO${NC}: $CL_WAITING clients waiting for connections"
    else
        echo -e "${GREEN}✓ OK${NC}: No clients waiting"
    fi

    # Max wait time alert
    if [ "$MAX_WAIT" -gt "$MAX_WAIT_WARNING" ]; then
        echo -e "${RED}🔴 CRITICAL${NC}: Maximum wait time is ${MAX_WAIT} seconds (threshold: ${MAX_WAIT_WARNING}s)"
        echo "  Action Required: Immediate investigation needed - possible deadlock or long-running query"
    elif [ "$MAX_WAIT" -gt 0 ]; then
        echo -e "${GREEN}✓ OK${NC}: Maximum wait time: ${MAX_WAIT} seconds"
    fi
else
    echo -e "${YELLOW}⚠️  WARNING${NC}: Unable to retrieve pool data for 'wellmed' database"
fi

echo ""

# Configuration Summary
echo -e "${BLUE}Configuration Summary${NC}"
echo "-----------------------------------------------------------"
PGPASSWORD=$PGPASSWORD psql -h $PGHOST -p $PGPORT -U $PGUSER -d pgbouncer -c "
SHOW CONFIG;
" 2>/dev/null | grep -E "^(pool_mode|max_client_conn|default_pool_size|min_pool_size|reserve_pool_size|server_reset_query|max_db_connections)" || echo "Failed to retrieve configuration"
echo ""

# Memory Usage (if running in Docker)
echo -e "${BLUE}PgBouncer Process Info${NC}"
echo "-----------------------------------------------------------"
if command -v docker &> /dev/null; then
    CONTAINER_NAME=$(docker ps --filter "expose=6432" --format "{{.Names}}" | head -1)
    if [ -n "$CONTAINER_NAME" ]; then
        echo "Container: $CONTAINER_NAME"
        docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}\t{{.NetIO}}" $CONTAINER_NAME
    fi
fi
echo ""

# Summary and Recommendations
echo "============================================================"
echo "  Summary"
echo "============================================================"
echo "Monitor completed at: $TIMESTAMP"
echo ""
echo "Recommended Actions:"
echo "1. If saturation > ${SATURATION_WARNING}%, consider increasing pool sizes"
echo "2. If clients are waiting, investigate slow queries"
echo "3. If max wait > ${MAX_WAIT_WARNING}s, check for deadlocks or long-running queries"
echo "4. Review pgbouncer logs for errors: docker logs <container_name>"
echo ""
echo "For detailed analysis, connect to pgbouncer admin:"
echo "  psql -h $PGHOST -p $PGPORT -U $PGUSER -d pgbouncer"
echo "============================================================"
