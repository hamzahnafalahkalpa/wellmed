# Docker ARM Setup for Wellmed

This folder contains ARM64-compatible Docker configurations for running Wellmed on ARM-based servers.

## Structure

```
docker-arm/
├── app/
│   └── Dockerfile          # ARM64 PHP application container
├── database/
│   ├── Dockerfile          # ARM64 PgBouncer container
│   ├── pgbouncer.ini       # PgBouncer configuration
│   └── userlist.txt        # Database authentication
└── README.md
```

## Components

### 1. App (Application Container)
- **Location:** `docker-arm/app/Dockerfile`
- **Base Image:** `dunglas/frankenphp:php8.4` (ARM64)
- **Purpose:** Runs the Wellmed Backbone PHP application
- **Features:**
  - PHP 8.4 with FrankenPHP
  - All required PHP extensions (pgsql, redis, sockets, etc.)
  - Supervisor for process management
  - Multi-stage build (development & production)

### 2. Database (PgBouncer Connection Pooler)
- **Location:** `docker-arm/database/`
- **Base Image:** `pgbouncer/pgbouncer:latest` (ARM64)
- **Purpose:** Connection pooling to remote PostgreSQL database
- **Configuration:**
  - **Remote Database Server:** `10.100.14.59:5432`
  - **Pool Mode:** Transaction (optimized for multi-tenant)
  - **Pool Size:** 30 connections (default)
  - **Port:** 6432 (exposed)

## Usage

### Build and Run

```bash
# Build and start all services
docker compose -f docker-compose-dev-arm.yaml up -d --build

# Build only app
docker compose -f docker-compose-dev-arm.yaml build wellmed

# Build only database (PgBouncer)
docker compose -f docker-compose-dev-arm.yaml build wellmed_pgbouncer

# View logs
docker compose -f docker-compose-dev-arm.yaml logs -f wellmed
docker compose -f docker-compose-dev-arm.yaml logs -f wellmed_pgbouncer
```

### Stop Services

```bash
docker compose -f docker-compose-dev-arm.yaml down
```

## Database Configuration

The PgBouncer is configured to connect to:
- **Host:** 10.100.14.59
- **Port:** 5432
- **Database:** wellmed
- **User:** postgres

### Updating Database Credentials

If you need to change the database password, update the `userlist.txt` file:

```bash
# Generate MD5 hash for password
echo -n "passwordUSERNAME" | md5sum

# Update docker-arm/database/userlist.txt with:
"USERNAME" "md5HASHHERE"
```

Then rebuild the PgBouncer container:

```bash
docker compose -f docker-compose-dev-arm.yaml build wellmed_pgbouncer
docker compose -f docker-compose-dev-arm.yaml up -d wellmed_pgbouncer
```

## Environment Variables

Make sure your `.env.backbone` file has the correct database settings:

```env
DB_CONNECTION=central
DB_HOST=wellmed_pgbouncer
DB_PORT=6432
DB_DATABASE=wellmed
DB_USERNAME=postgres
DB_PASSWORD=your_password_here
```

## Troubleshooting

### Check PgBouncer Connection

```bash
# Connect to PgBouncer admin console
docker exec -it wellmed-pgbouncer-arm psql -h localhost -p 6432 -U postgres pgbouncer

# View pool statistics
SHOW POOLS;
SHOW DATABASES;
SHOW CLIENTS;
```

### Check Application Logs

```bash
# Application logs
docker logs wellmed-backbone -f

# PgBouncer logs
docker logs wellmed-pgbouncer-arm -f
```

### Test Database Connectivity

```bash
# From application container
docker exec -it wellmed-backbone php artisan tinker --execute="DB::select('SELECT 1 as test');"
```

## Performance Tuning

### PgBouncer Settings

Edit `docker-arm/database/pgbouncer.ini` to adjust:

- `default_pool_size` - Number of server connections per database (default: 30)
- `max_client_conn` - Maximum client connections (default: 500)
- `query_timeout` - Maximum query execution time (default: 120s)
- `server_lifetime` - Connection recycling time (default: 3600s / 1 hour)

After changes, rebuild the container:

```bash
docker compose -f docker-compose-dev-arm.yaml build wellmed_pgbouncer
docker compose -f docker-compose-dev-arm.yaml up -d wellmed_pgbouncer
```

## Network Architecture

```
┌─────────────────┐
│  Application    │
│  (ARM64)        │
│  Port: 9000     │
└────────┬────────┘
         │
         │ connects to
         ▼
┌─────────────────┐
│  PgBouncer      │
│  (ARM64)        │
│  Port: 6432     │
└────────┬────────┘
         │
         │ connects to
         ▼
┌─────────────────┐
│  PostgreSQL     │
│  10.100.14.59   │
│  Port: 5432     │
└─────────────────┘
```

## Notes

- All containers are built for `linux/arm64` platform
- PgBouncer is configured to prevent memory leaks with connection recycling
- Transaction pooling mode is optimal for Laravel multi-tenant applications
- Connection pool prevents overwhelming the remote PostgreSQL server
