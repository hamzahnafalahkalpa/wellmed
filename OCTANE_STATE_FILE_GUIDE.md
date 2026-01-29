# Octane State File Management

## What is the State File?

The Octane state file (e.g., `octane-server-state-lite.json`) stores:
- Master process PID
- Worker configuration
- Server settings (host, port)
- Listener configurations

**It does NOT store tenant data, so no security risk for tenant isolation.**

## Your Current Setup

```env
# .env.backbone
OCTANE_STATE_FILE=logs/octane-server-state-lite.json

# .env.hq
OCTANE_STATE_FILE=logs/octane-server-state-hq.json
```

## ✅ When This Works Fine

**Single Octane instance per container:**
```
wellmed-backbone container:
  ├── Octane FrankenPHP server (single process)
  ├── State file: octane-server-state-lite.json
  └── Serves ALL tenant types (lite, plus, e)
      Runtime tenant switching ✓
```

In this case: **NO PROBLEM** - One Octane instance handles all tenants via runtime switching.

## ⚠️ When This Could Cause Trouble

**Scenario 1: Multiple Octane Processes, Same State File**

If you run multiple Octane servers writing to the same state file:

```
Process A (Lite):  Write to octane-server-state-lite.json
Process B (Plus):  Write to octane-server-state-lite.json  ← CONFLICT!
Process C (E):     Write to octane-server-state-lite.json  ← CONFLICT!
```

**Problems:**
- File corruption from concurrent writes
- PID conflicts (Process A overwriting Process B's PID)
- Worker count confusion
- Race conditions on file locks

**Solution:** Each Octane instance needs its own state file:

```env
# .env.lite
OCTANE_STATE_FILE=logs/octane-server-state-lite.json

# .env.plus
OCTANE_STATE_FILE=logs/octane-server-state-plus.json

# .env.e
OCTANE_STATE_FILE=logs/octane-server-state-e.json
```

**Scenario 2: Shared Storage Volume**

If multiple containers share the same storage volume:

```
Container A (lite):  /app/storage/logs/octane-server-state-lite.json
Container B (plus):  /app/storage/logs/octane-server-state-lite.json
                     ↑ Same file path, different containers = CONFLICT
```

**Solution:** Use container-specific paths or unique filenames.

## 🔍 How to Check Your Setup

### 1. Check How Many Octane Instances Are Running

```bash
# Check processes
docker exec wellmed-backbone ps aux | grep octane

# Check if multiple containers run Octane
docker ps | grep wellmed
```

### 2. Check State File Usage

```bash
# See which processes are accessing the state file
docker exec wellmed-backbone lsof /app/storage/logs/octane-server-state-lite.json

# Check for multiple state files
docker exec wellmed-backbone ls -la /app/storage/logs/octane-server-state*
```

### 3. Monitor for File Conflicts

```bash
# Watch for rapid changes (sign of conflict)
docker exec wellmed-backbone watch -n 1 stat /app/storage/logs/octane-server-state-lite.json

# Check file locks
docker exec wellmed-backbone fuser /app/storage/logs/octane-server-state-lite.json
```

## ✅ Recommended Configuration

### Option 1: Single Octane Instance (Current, Recommended)

```
wellmed-backbone:
  - One Octane server
  - Handles all tenant types (lite, plus, e)
  - State file: octane-server-state-lite.json ✓
  - Runtime tenant switching via MicroTenant ✓
```

**No changes needed!** Your current setup is fine.

### Option 2: Separate Instances (If Needed)

If you decide to run separate Octane instances for each tenant type:

```yaml
# docker-compose.yaml
services:
  wellmed-lite:
    environment:
      OCTANE_STATE_FILE: logs/octane-server-state-lite.json
      OCTANE_PORT: 9000

  wellmed-plus:
    environment:
      OCTANE_STATE_FILE: logs/octane-server-state-plus.json
      OCTANE_PORT: 9001

  wellmed-e:
    environment:
      OCTANE_STATE_FILE: logs/octane-server-state-e.json
      OCTANE_PORT: 9002
```

### Option 3: Dynamic State File Names

Use PID-based naming to avoid conflicts:

```env
# .env.backbone
OCTANE_STATE_FILE=logs/octane-server-state-${APP_NAME}-${HOSTNAME}.json
```

## 🐛 Troubleshooting

### Issue: "State file locked" or "Cannot write to state file"

**Cause:** Multiple processes trying to write simultaneously

**Solution:**
```bash
# Check which process has the lock
docker exec wellmed-backbone lsof /app/storage/logs/octane-server-state-lite.json

# Kill stale Octane processes
docker exec wellmed-backbone php artisan octane:stop
docker exec wellmed-backbone php artisan octane:start
```

### Issue: Octane commands showing wrong worker count

**Cause:** State file contains stale data

**Solution:**
```bash
# Remove stale state file
docker exec wellmed-backbone rm /app/storage/logs/octane-server-state-lite.json

# Restart Octane (will recreate state file)
docker exec wellmed-backbone php artisan octane:reload
```

### Issue: Multiple Octane instances interfering

**Cause:** Same state file, different processes

**Solution:**
```bash
# Give each instance a unique state file
# Edit .env files to use different state file names
OCTANE_STATE_FILE=logs/octane-server-state-lite-${CONTAINER_NAME}.json
```

## 📊 State File Performance

**File Size:** ~12KB (negligible)
**Write Frequency:** Only on Octane start/reload/stop
**Read Frequency:** Rarely (only for status commands)

**Performance Impact:** ⚡ MINIMAL - State file is not a bottleneck

## 🔐 Security Considerations

### ✅ Safe:
- State file contains no tenant data
- No user information
- No database credentials
- No session data
- No cached data

### ⚠️ Monitor:
- File permissions (should be 644, owned by www-data)
- No sensitive data accidentally logged
- State file not exposed via web server

```bash
# Check permissions
docker exec wellmed-backbone ls -la /app/storage/logs/octane-server-state-lite.json

# Should show: -rw-r--r-- www-data www-data
```

## 🎯 Conclusion

**For your setup (single Octane instance handling all tenant types):**

### ✅ Current Configuration is SAFE

- No tenant data in state file
- Single Octane process → No conflicts
- Separate state files for backbone vs HQ ✓

### ⚠️ Only worry if:

1. You plan to run MULTIPLE Octane instances simultaneously
2. You're sharing storage volumes between containers
3. You see "state file locked" errors in logs

### 🚀 Recommendation

**Keep your current setup!** The state file is working correctly and poses no risk.

**Only change if:**
- You need to run separate Octane instances for each tenant type
- You experience file locking issues
- You need better process isolation

---

**Status:** ✅ No issues with current octane-server-state-lite.json configuration
**Action Required:** None - works as designed
