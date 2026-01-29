# Octane FrankenPHP Memory Leak & Heartbeat Fixes

## Problems Identified

Your Octane FrankenPHP setup had several issues causing memory leaks and heartbeat errors:

1. ✗ **Low garbage collection threshold** (50MB) - caused frequent GC cycles
2. ✗ **No max_requests in supervisor** - workers never recycled
3. ✗ **Development mode uses --watch** - consumes extra memory monitoring files
4. ✗ **No memory limits** - no enforcement of memory boundaries
5. ✗ **Short stopwaitsecs** (10s) - insufficient time for graceful shutdown
6. ✗ **No health monitoring** - no automatic recovery from memory leaks

## Solutions Implemented

### 1. Updated Octane Configuration (`config/octane.php`)

**Changes:**
- ✓ Increased garbage collection threshold: `50MB → 256MB`
- ✓ Reduced max_requests: `1000 → 500` (more frequent worker recycling)
- ✓ Increased max_execution_time: `30s → 60s`
- ✓ Re-enabled `FlushUploadedFiles` listener
- ✓ Made all settings configurable via environment variables

**Environment Variables (add to `.env.backbone`):**
```bash
# Octane Performance Tuning
OCTANE_GARBAGE_COLLECTION=256
OCTANE_MAX_REQUESTS=500
OCTANE_MAX_EXECUTION_TIME=60
OCTANE_MAX_MEMORY=2048  # MB, for healthcheck monitor
OCTANE_CHECK_INTERVAL=60  # seconds, for healthcheck monitor
```

### 2. Updated Supervisor Configurations

**Development** (`docker/development/supervisor-octane.conf`):
- ✓ Added `--max-requests=500` flag
- ✓ Increased `stopwaitsecs: 10s → 30s` (graceful shutdown)
- ✓ Increased `startretries: 3 → 5`
- ✓ Added log rotation (10MB max, 3 backups)
- ✓ Set priority=10 for proper startup order

**Production** (`docker/production/supervisor-octane.conf`):
- ✓ Same improvements as development
- ✓ Removed `--watch` flag (production doesn't need file watching)

### 3. Created Health Monitoring System

**New File:** `docker/bin/octane-healthcheck.sh`

A background daemon that monitors:
- Memory usage (auto-restart if exceeded)
- Process health (checks if Octane is running)
- Heartbeat status (validates state file freshness)

**Features:**
- Checks every 60 seconds (configurable)
- Auto-restarts Octane if memory > 2GB (configurable)
- Logs all actions to `/app/supervisor/run/octane-healthcheck.log`
- Runs as a supervisor program

**New Supervisor Config:**
- `docker/development/supervisor-octane-monitor.conf`
- `docker/production/supervisor-octane-monitor.conf`

### 4. Updated Dockerfile

Added healthcheck script and monitor configuration to both production and development stages.

## How to Apply the Fixes

### Step 1: Update Environment Variables

Add to your `.env.backbone` file:

```bash
# Octane Configuration
OCTANE_SERVER=frankenphp
OCTANE_GARBAGE_COLLECTION=256
OCTANE_MAX_REQUESTS=500
OCTANE_MAX_EXECUTION_TIME=60

# Health Monitor Configuration
OCTANE_MAX_MEMORY=2048
OCTANE_CHECK_INTERVAL=60
```

### Step 2: Rebuild Docker Images

```bash
# Stop containers
docker-compose -f docker-compose-local.yaml down

# Rebuild images
docker-compose -f docker-compose-local.yaml build --no-cache wellmed

# Start containers
docker-compose -f docker-compose-local.yaml up -d
```

Or for development environment:
```bash
docker-compose -f docker-compose-dev.yaml down
docker-compose -f docker-compose-dev.yaml build --no-cache wellmed
docker-compose -f docker-compose-dev.yaml up -d
```

### Step 3: Verify the Fix

**Check if Octane is running:**
```bash
docker exec -it wellmed-backbone supervisorctl status
```

You should see:
```
backbone-octane                  RUNNING   pid 123, uptime 0:01:23
octane-monitor                   RUNNING   pid 124, uptime 0:01:23
```

**Check memory usage:**
```bash
docker exec -it wellmed-backbone ps aux | grep -E "octane|frankenphp" | grep -v grep
```

**View health monitor logs:**
```bash
docker exec -it wellmed-backbone tail -f /app/supervisor/run/octane-healthcheck.log
```

**View Octane logs:**
```bash
docker exec -it wellmed-backbone tail -f /app/supervisor/run/octane.out.log
docker exec -it wellmed-backbone tail -f /app/supervisor/run/octane.err.log
```

## Monitoring & Maintenance

### Real-time Monitoring

**Check Octane status:**
```bash
docker exec -it wellmed-backbone php artisan octane:status
```

**Monitor memory in real-time:**
```bash
docker exec -it wellmed-backbone watch -n 2 "ps aux | grep -E 'octane|frankenphp' | grep -v grep"
```

**View all supervisor logs:**
```bash
docker exec -it wellmed-backbone tail -f /app/supervisor/run/*.log
```

### Manual Operations

**Restart Octane manually:**
```bash
docker exec -it wellmed-backbone supervisorctl restart backbone-octane
```

**Reload Octane (graceful restart):**
```bash
docker exec -it wellmed-backbone php artisan octane:reload
```

**Stop Octane:**
```bash
docker exec -it wellmed-backbone supervisorctl stop backbone-octane
```

**Start Octane:**
```bash
docker exec -it wellmed-backbone supervisorctl start backbone-octane
```

**Restart health monitor:**
```bash
docker exec -it wellmed-backbone supervisorctl restart octane-monitor
```

## Performance Tuning

### If you still experience memory issues:

**Reduce max_requests further:**
```bash
OCTANE_MAX_REQUESTS=250  # Recycle workers more often
```

**Reduce garbage collection threshold:**
```bash
OCTANE_GARBAGE_COLLECTION=128  # More aggressive GC
```

**Reduce max memory limit:**
```bash
OCTANE_MAX_MEMORY=1536  # Restart at 1.5GB instead of 2GB
```

### If you have plenty of memory:

**Increase max_requests:**
```bash
OCTANE_MAX_REQUESTS=1000  # Less frequent recycling
```

**Increase garbage collection threshold:**
```bash
OCTANE_GARBAGE_COLLECTION=512  # Less frequent GC
```

**Increase max memory limit:**
```bash
OCTANE_MAX_MEMORY=3072  # Allow 3GB before restart
```

## Troubleshooting

### Symptom: "Missed heartbeat" errors

**Cause:** Worker taking too long to respond

**Solution:**
1. Check for long-running queries (optimize database)
2. Increase `OCTANE_MAX_EXECUTION_TIME`
3. Check for blocking operations in your code

### Symptom: Memory keeps growing

**Cause:** Memory leak in application code

**Solution:**
1. Reduce `OCTANE_MAX_REQUESTS` (recycle workers sooner)
2. Review your code for circular references
3. Check Octane logs for specific requests causing memory spikes

### Symptom: Workers keep restarting

**Cause:** Application errors or crashes

**Solution:**
1. Check error logs: `docker exec -it wellmed-backbone tail -f /app/supervisor/run/octane.err.log`
2. Check Laravel logs: `docker exec -it wellmed-backbone tail -f /app/storage/logs/laravel.log`
3. Increase `startretries` in supervisor config

### Symptom: Slow response times

**Cause:** Too aggressive worker recycling or GC

**Solution:**
1. Increase `OCTANE_MAX_REQUESTS`
2. Increase `OCTANE_GARBAGE_COLLECTION`
3. Check if healthcheck is restarting workers too often

## Additional Recommendations

### 1. Add Monitoring to Production

Consider adding these to your production monitoring:
- Memory usage alerts (when > 80% of max)
- Worker restart frequency
- Request latency
- Error rates

### 2. Configure Docker Resource Limits

Add to your `docker-compose*.yaml`:

```yaml
services:
  wellmed:
    # ... existing config ...
    deploy:
      resources:
        limits:
          cpus: '2'
          memory: 4G
        reservations:
          memory: 2G
```

### 3. Use Opcache in Production

Ensure opcache is enabled in your PHP configuration for better performance.

### 4. Consider Using Redis for Session/Cache

If not already using Redis, it can help reduce memory pressure on Octane workers.

## Summary of Changes

| Configuration | Before | After | Reason |
|--------------|--------|-------|--------|
| Garbage Collection | 50MB | 256MB | Reduce GC frequency |
| Max Requests | 1000 | 500 | More frequent worker recycling |
| Max Execution Time | 30s | 60s | Handle longer requests |
| Supervisor stopwaitsecs | 10s | 30s | Graceful shutdown |
| Supervisor startretries | 3 | 5 | Better resilience |
| FlushUploadedFiles | Disabled | Enabled | Prevent memory leaks |
| Health Monitor | None | Added | Auto-recovery |
| Log Rotation | None | 10MB/3 backups | Disk management |

## Need Help?

If you continue experiencing issues:

1. Share the logs from:
   - `/app/supervisor/run/octane.err.log`
   - `/app/supervisor/run/octane-healthcheck.log`
   - `/app/storage/logs/laravel.log`

2. Check memory usage patterns:
   ```bash
   docker stats wellmed-backbone
   ```

3. Profile your application to identify memory-intensive operations
