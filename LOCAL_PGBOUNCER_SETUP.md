# PgBouncer Setup for Local Development (docker-compose-local.yaml)

## Overview

In your local setup:
- **PostgreSQL** runs on your host machine (not in Docker) at `localhost:5433`
- **PgBouncer** runs in Docker and connects to your host PostgreSQL
- **Laravel app** (in Docker) connects to PgBouncer

## Configuration Steps

### 1. Find Your Local PostgreSQL Configuration

Your PostgreSQL is likely in `/var/www/caddy` or standard locations:

```bash
# Find postgresql.conf location
sudo -u postgres psql -c "SHOW config_file;"

# Find pg_hba.conf location
sudo -u postgres psql -c "SHOW hba_file;"
```

Common locations:
- `/var/www/caddy/pgsql/data/postgresql.conf`
- `/var/lib/postgresql/data/postgresql.conf`
- `/etc/postgresql/15/main/postgresql.conf`

### 2. Configure PostgreSQL for MD5 Authentication

PgBouncer requires MD5 password encryption. Edit your `postgresql.conf`:

```bash
# Edit postgresql.conf
sudo nano /path/to/postgresql.conf

# Add or modify this line:
password_encryption = md5
```

**Save and reload:**
```bash
sudo -u postgres psql -c "SELECT pg_reload_conf();"
```

### 3. Update pg_hba.conf

Edit `pg_hba.conf` to allow MD5 authentication:

```bash
sudo nano /path/to/pg_hba.conf
```

**Add these lines at the top:**
```
# TYPE  DATABASE        USER            ADDRESS                 METHOD
host    all             all             127.0.0.1/32            md5
host    all             all             172.16.0.0/12           md5  # Docker network
host    all             all             0.0.0.0/0               md5  # All (for development only)
```

**Reload configuration:**
```bash
sudo -u postgres psql -c "SELECT pg_reload_conf();"
# OR restart PostgreSQL
sudo systemctl restart postgresql
```

### 4. Reset Postgres User Password to MD5

```bash
# Connect to PostgreSQL
sudo -u postgres psql

# Reset password (will now use MD5 encryption)
ALTER USER postgres WITH PASSWORD 'password123';

# Verify it's MD5 (should NOT show 'SCRAM-SHA-256')
SELECT rolname, rolpassword FROM pg_authid WHERE rolname = 'postgres';
# Password should start with 'md5...' not 'SCRAM-SHA-256...'

\q
```

### 5. Update Your .env.backbone

Update your local `.env.backbone` to use PgBouncer:

```bash
# Database Configuration
DB_DRIVER=pgsql
DB_CONNECTION=central

# Use PgBouncer (running in Docker)
DB_HOST=localhost        # PgBouncer is exposed on localhost
DB_PORT=6432            # PgBouncer port

# OR use container name if app is in Docker
#DB_HOST=wellmed-pgbouncer-local
#DB_PORT=5432           # Internal container port

DB_DATABASE=wellmed
DB_USERNAME=postgres
DB_PASSWORD=password123
```

### 6. Start PgBouncer

```bash
# Stop any existing pgbouncer
docker-compose -f docker-compose-local.yaml stop wellmed_pgbouncer
docker-compose -f docker-compose-local.yaml rm -f wellmed_pgbouncer

# Start PgBouncer
docker-compose -f docker-compose-local.yaml up -d wellmed_pgbouncer

# Check logs
docker logs wellmed-pgbouncer-local

# Should see:
# "LOG kernel file descriptor limit: 1048576 (hard: 1048576); max_client_conn: 1000"
# "LOG listening on 0.0.0.0:5432"
# "LOG process up: PgBouncer 1.25.1"
```

### 7. Test Connection

```bash
# Test from host machine
PGPASSWORD=password123 psql -h localhost -p 6432 -U postgres -d wellmed -c "SELECT current_database();"

# Should return:
#  current_database
# ------------------
#  wellmed
# (1 row)

# Test from Laravel container
docker exec wellmed-backbone php artisan tinker
# Then run:
DB::connection()->getPdo();
# Should connect without errors
```

## Troubleshooting

### Error: "wrong password type"

**Cause:** PostgreSQL is using SCRAM-SHA-256 instead of MD5

**Solution:**
1. Set `password_encryption = md5` in postgresql.conf
2. Reset postgres user password: `ALTER USER postgres WITH PASSWORD 'password123';`
3. Restart PostgreSQL

### Error: "connection refused"

**Cause:** PgBouncer cannot reach host PostgreSQL

**Solution:**
```bash
# Verify PostgreSQL is listening on all interfaces
sudo -u postgres psql -c "SHOW listen_addresses;"
# Should show: 0.0.0.0 or *

# If it shows 'localhost', edit postgresql.conf:
listen_addresses = '*'  # or '0.0.0.0'

# Restart PostgreSQL
sudo systemctl restart postgresql
```

### Error: "no pg_hba.conf entry"

**Cause:** pg_hba.conf not allowing Docker network

**Solution:**
Add to pg_hba.conf:
```
host    all             all             172.16.0.0/12           md5
```

### Test Direct PostgreSQL Connection

```bash
# From host machine
PGPASSWORD=password123 psql -h localhost -p 5433 -U postgres -d wellmed -c "SELECT 1;"

# From Docker container
docker run --rm -it postgres:15 bash -c "PGPASSWORD=password123 psql -h host.docker.internal -p 5433 -U postgres -d wellmed -c 'SELECT 1;'"
```

### Check PgBouncer Stats

```bash
# Install psql client in pgbouncer container
docker exec wellmed-pgbouncer-local sh -c "apk add postgresql-client"

# Then check stats
docker exec wellmed-pgbouncer-local psql -h localhost -p 5432 -U postgres -d pgbouncer -c "SHOW POOLS;"
```

## Architecture Diagram

```
┌─────────────────────────────────────────────────────────────┐
│ Host Machine (Your Computer)                                │
│                                                              │
│  ┌──────────────────────────────────────────────┐          │
│  │ PostgreSQL (Native)                          │          │
│  │ Port: 5433                                   │          │
│  │ Password: MD5 encrypted                      │          │
│  └────────────────┬─────────────────────────────┘          │
│                   │                                          │
│                   │ Connection                               │
│                   ▼                                          │
│  ┌──────────────────────────────────────────────┐          │
│  │ Docker: PgBouncer Container                  │          │
│  │ Container Port: 5432                         │          │
│  │ Host Port: 6432                              │          │
│  │ Pool Mode: transaction                       │          │
│  │ Max Connections: 1000 → 25 per database      │          │
│  └────────────────┬─────────────────────────────┘          │
│                   │                                          │
│                   │ Connection                               │
│                   ▼                                          │
│  ┌──────────────────────────────────────────────┐          │
│  │ Docker: Laravel App (wellmed-backbone)       │          │
│  │ Port: 9000                                   │          │
│  │ Connects to: wellmed-pgbouncer-local:5432    │          │
│  │ or localhost:6432                            │          │
│  └──────────────────────────────────────────────┘          │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

## Benefits

1. **Connection Pooling** - Reduces overhead of creating new connections
2. **Better Performance** - 1000 client connections → 25 server connections
3. **Deadlock Protection** - Combined with optimized transaction logic
4. **Dynamic Database Support** - Works with all tenant databases (clinic_1, clinic_2, etc.)

## Alternative: Without PgBouncer (Simpler Setup)

If you have trouble with PgBouncer, you can temporarily bypass it:

**In `.env.backbone`:**
```bash
# Direct connection (no PgBouncer)
DB_HOST=host.docker.internal
DB_PORT=5433
DB_DATABASE=wellmed
DB_USERNAME=postgres
DB_PASSWORD=password123
```

But note: You'll lose connection pooling benefits and may experience more deadlocks under load.

## Next Steps

Once PgBouncer is working:

1. **Monitor connections:**
   ```bash
   # Check active connections
   docker logs wellmed-pgbouncer-local | grep -i "connection"
   ```

2. **Test with your app:**
   ```bash
   docker-compose -f docker-compose-local.yaml up -d
   docker logs -f wellmed-backbone
   ```

3. **Verify no deadlocks:**
   ```bash
   # Check Laravel logs
   docker exec wellmed-backbone tail -f storage/logs/laravel.log | grep -i deadlock
   ```

## Need Help?

Check the main documentation: `POSTGRES_OPTIMIZATION.md`

Or test step by step:
1. PostgreSQL direct connection ✓
2. PgBouncer container running ✓
3. PgBouncer → PostgreSQL connection ✓
4. Laravel → PgBouncer connection ✓
