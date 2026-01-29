# Setup Comparison: Local vs Development Environments

## Quick Reference

| Aspect | docker-compose-local.yaml | docker-compose-dev.yaml |
|--------|---------------------------|-------------------------|
| **PostgreSQL** | Host machine (port 5433) | Docker container |
| **PgBouncer** | Docker container | Docker container |
| **Laravel App** | Docker container | Docker container |
| **PgBouncer Port** | `localhost:6432` (from host)<br>`wellmed-pgbouncer-local:5432` (from container) | `wellmed-pgbouncer:5432` |
| **Setup Complexity** | Medium (need to config host PostgreSQL) | Easy (all in Docker) |
| **Use Case** | Local development on your computer | Team development / CI/CD |

## Environment Variable Summary

### For docker-compose-local.yaml

**Option 1: From Host Machine (outside Docker)**
```bash
# .env.backbone
DB_HOST=localhost
DB_PORT=6432
DB_DATABASE=wellmed
DB_USERNAME=postgres
DB_PASSWORD=password123
```

**Option 2: From Laravel Container (inside Docker)**
```bash
# .env.backbone
DB_HOST=wellmed-pgbouncer-local
DB_PORT=5432
DB_DATABASE=wellmed
DB_USERNAME=postgres
DB_PASSWORD=password123
```

### For docker-compose-dev.yaml

```bash
# .env.backbone
DB_HOST=wellmed-pgbouncer  # or wellmed_pgbouncer
DB_PORT=5432
DB_DATABASE=wellmed
DB_USERNAME=postgres
DB_PASSWORD=Password@123
```

## Setup Instructions

### Local Environment (docker-compose-local.yaml)

**Prerequisites:**
- PostgreSQL running on your host machine at port 5433
- Located in `/var/www/caddy` or standard location

**Steps:**

1. **Configure PostgreSQL for MD5:**
   ```bash
   # Edit postgresql.conf
   sudo nano /var/lib/postgresql/data/postgresql.conf

   # Add:
   password_encryption = md5
   listen_addresses = '*'

   # Edit pg_hba.conf
   sudo nano /var/lib/postgresql/data/pg_hba.conf

   # Add at top:
   host    all    all    172.16.0.0/12    md5
   host    all    all    0.0.0.0/0        md5

   # Restart
   sudo systemctl restart postgresql
   ```

2. **Reset postgres password:**
   ```bash
   sudo -u postgres psql
   ALTER USER postgres WITH PASSWORD 'password123';
   \q
   ```

3. **Start PgBouncer:**
   ```bash
   docker-compose -f docker-compose-local.yaml up -d wellmed_pgbouncer
   ```

4. **Test connection:**
   ```bash
   ./docker/bin/test-pgbouncer-local.sh
   ```

5. **Update .env.backbone:**
   ```bash
   DB_HOST=localhost
   DB_PORT=6432
   ```

6. **Start Laravel:**
   ```bash
   docker-compose -f docker-compose-local.yaml up -d wellmed
   ```

**Troubleshooting:** See `LOCAL_PGBOUNCER_SETUP.md`

### Development Environment (docker-compose-dev.yaml)

**Prerequisites:**
- None! Everything runs in Docker

**Steps:**

1. **Start everything:**
   ```bash
   docker-compose -f docker-compose-dev.yaml up -d
   ```

2. **That's it!** PostgreSQL and PgBouncer are automatically configured

3. **Test connection:**
   ```bash
   docker exec wellmed-postgres bash -c \
     "PGPASSWORD=Password@123 psql -h wellmed-pgbouncer -p 5432 -U postgres -d wellmed -c 'SELECT 1;'"
   ```

## Common Commands

### Check PgBouncer Status

**Local:**
```bash
docker logs wellmed-pgbouncer-local
```

**Dev:**
```bash
docker logs wellmed-pgbouncer
```

### Test Database Connection

**Local (from host):**
```bash
PGPASSWORD=password123 psql -h localhost -p 6432 -U postgres -d wellmed -c "SELECT current_database();"
```

**Dev (from container):**
```bash
docker exec wellmed-postgres bash -c \
  "PGPASSWORD=Password@123 psql -h wellmed-pgbouncer -p 5432 -U postgres -d wellmed -c 'SELECT current_database();'"
```

### Restart Services

**Local:**
```bash
docker-compose -f docker-compose-local.yaml restart wellmed_pgbouncer wellmed
```

**Dev:**
```bash
docker-compose -f docker-compose-dev.yaml restart wellmed_pgbouncer wellmed
```

### View Logs

**Local:**
```bash
# PgBouncer
docker logs -f wellmed-pgbouncer-local

# Laravel
docker logs -f wellmed-backbone

# PostgreSQL (on host)
sudo tail -f /var/log/postgresql/postgresql-15-main.log
```

**Dev:**
```bash
# PgBouncer
docker logs -f wellmed-pgbouncer

# Laravel
docker logs -f wellmed-backbone

# PostgreSQL
docker logs -f wellmed-postgres
```

## Architecture Diagrams

### Local Environment (docker-compose-local.yaml)

```
┌──────────────────────────────────────────────────────────────┐
│ YOUR COMPUTER (Host Machine)                                 │
│                                                               │
│  ┌────────────────────────────────────────┐                 │
│  │ PostgreSQL (Native Install)            │                 │
│  │ Location: /var/www/caddy or /var/lib/  │                 │
│  │ Port: 5433 (exposed to host)           │                 │
│  │ Auth: MD5                               │                 │
│  └───────────────┬────────────────────────┘                 │
│                  │                                            │
│  ┌───────────────┼──────── Docker ─────────────────────┐    │
│  │               ▼                                      │    │
│  │  ┌─────────────────────────────────────┐            │    │
│  │  │ PgBouncer Container                 │            │    │
│  │  │ Name: wellmed-pgbouncer-local       │            │    │
│  │  │ Internal Port: 5432                 │            │    │
│  │  │ External Port: 6432                 │            │    │
│  │  │ Connects to: host.docker.internal   │            │    │
│  │  └───────────────┬─────────────────────┘            │    │
│  │                  │                                    │    │
│  │                  ▼                                    │    │
│  │  ┌─────────────────────────────────────┐            │    │
│  │  │ Laravel App Container               │            │    │
│  │  │ Name: wellmed-backbone              │            │    │
│  │  │ Port: 9000                          │            │    │
│  │  │ DB_HOST: wellmed-pgbouncer-local    │            │    │
│  │  │ DB_PORT: 5432                       │            │    │
│  │  └─────────────────────────────────────┘            │    │
│  └──────────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────────┘
```

### Development Environment (docker-compose-dev.yaml)

```
┌───────────────────────────────────────────────────────────────┐
│ Docker Network: wellmed-core                                  │
│                                                                │
│  ┌─────────────────────────────────────┐                     │
│  │ PostgreSQL Container                │                     │
│  │ Name: wellmed-postgres              │                     │
│  │ Internal Port: 5432                 │                     │
│  │ External Port: 5433                 │                     │
│  │ Auth: MD5                           │                     │
│  └────────────────┬────────────────────┘                     │
│                   │                                            │
│                   ▼                                            │
│  ┌─────────────────────────────────────┐                     │
│  │ PgBouncer Container                 │                     │
│  │ Name: wellmed-pgbouncer             │                     │
│  │ Internal Port: 5432                 │                     │
│  │ External Port: 6432                 │                     │
│  │ Connects to: wellmed-postgres:5432  │                     │
│  └────────────────┬────────────────────┘                     │
│                   │                                            │
│                   ▼                                            │
│  ┌─────────────────────────────────────┐                     │
│  │ Laravel App Container               │                     │
│  │ Name: wellmed-backbone              │                     │
│  │ Port: 9000                          │                     │
│  │ DB_HOST: wellmed-pgbouncer          │                     │
│  │ DB_PORT: 5432                       │                     │
│  └─────────────────────────────────────┘                     │
│                                                                │
└───────────────────────────────────────────────────────────────┘
```

## Which Setup Should I Use?

### Use **docker-compose-local.yaml** when:
- ✓ You're developing on your local computer
- ✓ You already have PostgreSQL installed on your machine
- ✓ You want to use your existing PostgreSQL data
- ✓ You're familiar with managing PostgreSQL on your host OS

### Use **docker-compose-dev.yaml** when:
- ✓ You want everything in Docker (isolated)
- ✓ You're setting up a new environment
- ✓ You're deploying to staging/production
- ✓ Multiple team members need identical setups
- ✓ You're using CI/CD pipelines

## Need More Help?

- **Local setup issues:** See `LOCAL_PGBOUNCER_SETUP.md`
- **General PostgreSQL optimization:** See `POSTGRES_OPTIMIZATION.md`
- **Transaction deadlocks:** Check the optimized `HasRequest.php` transaction logic
- **Test script:** Run `./docker/bin/test-pgbouncer-local.sh`
