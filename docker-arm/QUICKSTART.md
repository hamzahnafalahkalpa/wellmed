# Quick Start Guide - ARM Deployment

## Overview
This ARM setup includes:
- **App Container**: ARM64-compatible PHP 8.4 application
- **PgBouncer**: Connection pooler to remote PostgreSQL at `10.100.14.59`

## Prerequisites
- Docker with ARM64 support
- `.env.backbone` file configured

## Environment Setup

Update your `.env.backbone` with these database settings:

```env
DB_CONNECTION=central
DB_HOST=wellmed_pgbouncer
DB_PORT=6432
DB_DATABASE=wellmed
DB_USERNAME=postgres
DB_PASSWORD=Password@123
```

## Quick Start

### Option 1: Using Deploy Script (Recommended)

```bash
# Start everything
./docker-arm/deploy.sh up

# Check status
./docker-arm/deploy.sh status

# View logs
./docker-arm/deploy.sh logs wellmed
./docker-arm/deploy.sh logs wellmed_pgbouncer

# Test database connection
./docker-arm/deploy.sh db-test

# Stop everything
./docker-arm/deploy.sh down
```

### Option 2: Using Docker Compose

```bash
# Start all services
docker compose -f docker-compose-dev-arm.yaml up -d --build

# View logs
docker compose -f docker-compose-dev-arm.yaml logs -f

# Stop all services
docker compose -f docker-compose-dev-arm.yaml down
```

## Verify Deployment

### 1. Check Containers
```bash
docker ps | grep wellmed
```

You should see:
- `wellmed-backbone` (app on port 9000)
- `wellmed-listener` (worker on port 9003)
- `wellmed-pgbouncer-arm` (database proxy on port 6432)

### 2. Test PgBouncer Connection
```bash
docker exec wellmed-pgbouncer-arm psql -h localhost -p 6432 -U postgres pgbouncer -c "SHOW POOLS;"
```

### 3. Test Application Database Connection
```bash
docker exec wellmed-backbone php artisan tinker --execute="DB::select('SELECT 1 as test');"
```

### 4. Access Application
Open browser: `http://your-server-ip:9000`

## Troubleshooting

### PgBouncer Can't Connect to Database
```bash
# Check PgBouncer logs
docker logs wellmed-pgbouncer-arm

# Verify network connectivity from container
docker exec wellmed-pgbouncer-arm ping -c 3 10.100.14.59
```

### Application Can't Connect to PgBouncer
```bash
# Check if PgBouncer is running
docker ps | grep pgbouncer

# Check application logs
docker logs wellmed-backbone

# Verify network
docker exec wellmed-backbone ping -c 3 wellmed_pgbouncer
```

### Rebuild Containers
```bash
# Rebuild everything from scratch
./docker-arm/deploy.sh rebuild

# Or rebuild specific service
./docker-arm/deploy.sh rebuild wellmed_pgbouncer
```

## Common Commands

```bash
# Enter application shell
docker exec -it wellmed-backbone bash

# Enter PgBouncer shell
docker exec -it wellmed-pgbouncer-arm sh

# Run artisan commands
docker exec wellmed-backbone php artisan migrate
docker exec wellmed-backbone php artisan cache:clear

# View real-time logs
docker logs -f wellmed-backbone
docker logs -f wellmed-pgbouncer-arm
```

## Performance Monitoring

### Check PgBouncer Stats
```bash
docker exec wellmed-pgbouncer-arm psql -h localhost -p 6432 -U postgres pgbouncer << 'SQL'
SHOW POOLS;
SHOW DATABASES;
SHOW CLIENTS;
SHOW STATS;
SQL
```

### Check Container Resources
```bash
docker stats wellmed-backbone wellmed-pgbouncer-arm
```

## Update Database Password

If you need to change the PostgreSQL password:

1. Generate MD5 hash:
```bash
echo -n "Password@123postgres" | md5sum
# Result: md55e68e8c8ac6ee5d8b6e3263708ca5cea
```

2. Update `docker-arm/database/userlist.txt`:
```
"postgres" "md55e68e8c8ac6ee5d8b6e3263708ca5cea"
```

3. Rebuild PgBouncer:
```bash
./docker-arm/deploy.sh rebuild wellmed_pgbouncer
```

## Support

For detailed documentation, see `docker-arm/README.md`
