# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Wellmed is a multi-tenant healthcare management system built with Laravel 12 and PHP 8.4. It uses Laravel Octane (FrankenPHP) for high-performance request handling and supports multiple products through a modular architecture.

### Multi-Application Architecture

This monorepo contains **multiple Laravel applications** that share common code but run as separate services:

- **wellmed-backbone** - Main API service (port 9000)
- **wellmed-hq** - Headquarters/admin service (port 9001)
- **wellmed-lite** - Lightweight version
- **wellmed-plus** - Extended features version
- **wellmed-gateway** - API gateway
- **wellmed-satu-sehat** - Indonesian health system integration

Each application has its own:
- Composer dependencies (`composer_backbone.json`, `composer_hq.json`, etc.)
- Environment file (`.env.backbone`, `.env.hq`, etc.)
- Route file (`routes/api-backbone.php`, `routes/api-hq.php`, etc.)
- Docker container with isolated volumes

### Key Architectural Patterns

**Modular Structure:**
- `projects/` - Main application modules (wellmed-backbone, hq, lite, plus, etc.)
- `repositories/` - Reusable packages and modules (60+ modules)
- `features/` - Microservice-style feature modules (ms-emr, ms-apotek, ms-hr, ms-scm, etc.)
- `app/` - Shared application code across all services

**Multi-Tenancy:**
- Uses `hanafalah/microtenant` package for tenant isolation
- Hybrid architecture with both separate databases AND schemas (see detailed section below)
- Tenant context is determined per-request and must be properly isolated in Octane
- Critical: Tenant state MUST be flushed between requests to prevent data leakage (see Octane section)

**Database Architecture:**
- PostgreSQL 15 with optimized configuration for healthcare workloads
- PgBouncer for connection pooling (port 6432 exposed, connects to PostgreSQL internally)
- Redis for caching and sessions
- RabbitMQ for queue management

### Multi-Tenant Database Architecture (CRITICAL)

Wellmed uses a **hybrid multi-tenant architecture** that combines both separate databases and schemas within databases. Understanding this is critical for proper tenant isolation and avoiding "Database does not exist" errors.

#### Database Structure

**1. Core Database: `wellmed`**
- Contains central data: users, tenants table, central configuration
- Contains **schemas** for APP-level and CENTRAL_TENANT-level tenants:
  - `app_2` - Wellmed Lite APP (schema within wellmed database)
  - `hq_1` - HQ APP (schema within wellmed database)
  - `group_3` - CENTRAL_TENANT (schema within wellmed database)
  - `public` - Default PostgreSQL schema

**2. Tenant Databases: `clinic_4`, `clinic_5`, etc.**
- **Separate PostgreSQL databases** for each TENANT-level tenant
- Each tenant database contains multiple schemas for yearly data partitioning:
  - `emr_2026` - Electronic Medical Records for 2026
  - `pos_2026` - Point of Sale for 2026
  - `scm_2026` - Supply Chain Management for 2026
  - `public` - Default schema
- This allows for efficient data archiving and performance optimization

#### Tenant Hierarchy and Database Assignment

```
Tenant ID 1: "Hq" (flag='APP')
  └─> Uses schema 'hq_1' in 'wellmed' database

Tenant ID 2: "Wellmed Lite" (flag='APP')
  └─> Uses schema 'app_2' in 'wellmed' database
      └─> Tenant ID 3: "Wellmed Lite" (flag='CENTRAL_TENANT', parent=2)
          └─> Uses schema 'group_3' in 'wellmed' database
              └─> Tenant ID 4: "Tenant Wellmed Lite" (flag='TENANT', parent=3)
                  └─> Uses separate database 'clinic_4' with schemas (emr_2026, pos_2026, etc.)
```

#### Database Naming Convention

Configured in `config/micro-tenant.php`:
- **APP tenants**: `app_tenant_` prefix + tenant_id → `app_2`
- **CENTRAL_TENANT**: `group_` prefix + tenant_id → `group_3`
- **TENANT**: `clinic_` prefix + tenant_id → `clinic_4`

#### Critical Implementation Detail: PostgreSQLSchemaManager

The `repositories/klinik-starterpack/src/Database/Manager/PostgreSQLSchemaManager.php` file manages database/schema creation and existence checks.

**IMPORTANT:** The `databaseExists()` method MUST check BOTH:
1. `pg_database` catalog (for separate TENANT databases like `clinic_4`)
2. `information_schema.schemata` (for APP/CENTRAL_TENANT schemas like `app_2`, `group_3`)

**Common Bug:** If `databaseExists()` only checks schemas, it will fail to find TENANT-level databases and throw "Database clinic_X does not exist" errors during sign-in.

**Correct Implementation:**
```php
public function databaseExists(string $name): bool
{
    // Check if it's a database first (for TENANT-level tenants)
    $databaseExists = (bool) $this->database()->select("SELECT datname FROM pg_database WHERE datname = '$name'");

    if ($databaseExists) {
        return true;
    }

    // If not a database, check if it's a schema (for APP/CENTRAL_TENANT)
    return (bool) $this->database()->select("SELECT schema_name FROM information_schema.schemata WHERE schema_name = '$name'");
}
```

#### Cluster Schemas for Yearly Partitioning

Within each tenant database (e.g., `clinic_4`), yearly schemas are created dynamically:
- Configuration in `config/database.php` defines clusters
- Each cluster has a `search_path` (schema name) based on the year
- Jobs can generate cluster schemas asynchronously
- This allows historical data to be partitioned by year for better performance

#### PgBouncer Configuration

PgBouncer (`docker/pgbouncer/local/pgbouncer.ini`) is configured to handle both:
- Explicit database routing (e.g., `wellmed`, `clinic_4`)
- Wildcard routing (`*`) for dynamic tenant databases
- Transaction pooling mode for search_path switching within sessions

**Wildcard configuration:**
```ini
[databases]
wellmed = host=host.docker.internal port=5432 dbname=wellmed
* = host=host.docker.internal port=5432
```

This allows PgBouncer to route connections to any tenant database without explicit configuration.

## Common Development Commands

### Docker Environment

**Start development environment:**
```bash
docker-compose -f docker-compose-dev.yaml up -d
```

**Start local environment (uses host PostgreSQL):**
```bash
docker-compose -f docker-compose-local.yaml up -d
```

**Rebuild specific service:**
```bash
docker-compose -f docker-compose-dev.yaml build --no-cache wellmed
docker-compose -f docker-compose-dev.yaml up -d wellmed
```

**View logs:**
```bash
docker logs -f wellmed-backbone
docker logs -f wellmed-hq
docker exec -it wellmed-backbone tail -f /app/supervisor/run/octane.out.log
docker exec -it wellmed-backbone tail -f /app/supervisor/run/octane.err.log
```

### Laravel Artisan (inside container)

**Execute commands in backbone:**
```bash
docker exec -it wellmed-backbone php artisan <command>
```

**Execute commands in HQ:**
```bash
docker exec -it wellmed-hq php artisan <command>
```

**Common commands:**
```bash
# Migrations
docker exec -it wellmed-backbone php artisan migrate
docker exec -it wellmed-backbone php artisan migrate:rollback

# Cache clearing
docker exec -it wellmed-backbone php artisan cache:clear
docker exec -it wellmed-backbone php artisan config:clear
docker exec -it wellmed-backbone php artisan route:clear

# Octane management
docker exec -it wellmed-backbone php artisan octane:status
docker exec -it wellmed-backbone php artisan octane:reload
docker exec -it wellmed-backbone supervisorctl status
docker exec -it wellmed-backbone supervisorctl restart backbone-octane
```

### Tailwind CSS Building

**Build for backbone (production):**
```bash
npm run build:backbone
```

**Watch for backbone (development):**
```bash
npm run dev:backbone
# Or use the helper script:
./watch.sh
```

**Build for HQ:**
```bash
npm run build:hq
```

### Testing

**Run tests:**
```bash
docker exec -it wellmed-backbone php artisan test
```

**Run specific test file:**
```bash
docker exec -it wellmed-backbone php artisan test --filter=TestClassName
```

**Run Pest tests:**
```bash
docker exec -it wellmed-backbone vendor/bin/pest
```

### Database Operations

**Access PostgreSQL directly:**
```bash
docker exec -it wellmed-postgres psql -U postgres -d wellmed
```

**Access through PgBouncer:**
```bash
PGPASSWORD=Password@123 psql -h localhost -p 6432 -U postgres -d wellmed
```

**Check connection status:**
```bash
./docker/bin/test-pgbouncer-local.sh
```

## Laravel Octane Configuration (CRITICAL)

This application uses **Laravel Octane with FrankenPHP** for high-performance request handling. Understanding Octane behavior is critical when making code changes.

### Octane Behavior Differences

Unlike traditional PHP-FPM, Octane keeps your application in memory between requests:
- Application bootstrap happens ONCE, not per request
- Static variables and singletons persist between requests
- Database connections are pooled and reused
- Configuration is cached in memory

### Multi-Tenant Isolation Requirements

**CRITICAL:** In this multi-tenant system, state MUST be isolated between requests to prevent tenant data leakage.

**Implemented safeguards:**
1. `app/Listeners/Octane/FlushTenantState.php` - Custom listener that runs after each request
2. `config/octane.php` flush configuration includes `MicroTenant` and `Tenancy` classes
3. Octane workers are recycled every 500 requests (`OCTANE_MAX_REQUESTS=500`)
4. Garbage collection threshold set to 256MB (`OCTANE_GARBAGE_COLLECTION=256`)

**When adding new features:**
- NEVER store tenant-specific data in static properties
- NEVER cache tenant data in class-level variables
- ALWAYS use request-scoped services for tenant context
- Test with multiple tenants to verify no state leakage

### Octane Reload Workflow

When you make code changes, you MUST reload Octane:

```bash
docker exec -it wellmed-backbone php artisan octane:reload
```

**Watch scripts** are provided for auto-reload during development:
- `./watch.sh` - Auto-reload backbone on file changes
- `./watch-hq.sh` - Auto-reload HQ on file changes
- `./watch-lite.sh` - Auto-reload lite on file changes

### Octane Performance Tuning

Environment variables in `.env.backbone`:
```bash
OCTANE_SERVER=frankenphp
OCTANE_GARBAGE_COLLECTION=256    # MB before GC runs
OCTANE_MAX_REQUESTS=500          # Requests before worker recycle
OCTANE_MAX_EXECUTION_TIME=60     # Seconds
OCTANE_MAX_MEMORY=2048          # MB before health monitor restarts
OCTANE_CHECK_INTERVAL=60        # Health check interval in seconds
```

Adjust these based on your workload. See `OCTANE_FIXES.md` for detailed tuning guide.

### Octane Health Monitoring

A health monitor (`docker/bin/octane-healthcheck.sh`) runs in the background:
- Checks memory usage every 60 seconds
- Auto-restarts Octane if memory exceeds threshold
- Logs to `/app/supervisor/run/octane-healthcheck.log`

**Check health status:**
```bash
docker exec -it wellmed-backbone supervisorctl status octane-monitor
docker exec -it wellmed-backbone tail -f /app/supervisor/run/octane-healthcheck.log
```

### Debugging Octane Issues

**Symptoms and solutions:**
- "Missed heartbeat" errors → Check `OCTANE_FIXES.md`
- Memory keeps growing → Reduce `OCTANE_MAX_REQUESTS`, check for leaks
- Tenant data leaking → Verify `FlushTenantState` listener is registered
- Workers keep restarting → Check error logs in `/app/supervisor/run/octane.err.log`

## Database and PgBouncer

### Connection Pooling

PgBouncer manages database connections for better performance under load:
- Transaction pooling mode (connections released after transaction)
- Max 200 client connections configured
- Pool size tuned for tenant isolation requirements

**Environment setup:**
```bash
DB_HOST=wellmed-pgbouncer  # Use PgBouncer, not direct PostgreSQL
DB_PORT=5432               # Internal port (6432 is external)
DB_CONNECTION=pgsql
```

### Local vs Development Setup

Two Docker Compose configurations exist:
- `docker-compose-dev.yaml` - Everything in Docker (recommended for teams)
- `docker-compose-local.yaml` - Uses host PostgreSQL + Docker PgBouncer

See `SETUP_COMPARISON.md` for detailed comparison and `LOCAL_PGBOUNCER_SETUP.md` for local setup.

## Project-Specific Modules

### Repositories Directory

Contains 60+ reusable Laravel packages:
- `microtenant/` - Multi-tenancy engine
- `module-*` - Domain modules (appointment, license, mcu, medical-treatment, tax, etc.)
- `laravel-*` - Laravel extensions (feature flags, permissions, stubs, support)
- `satu-sehat/` - Indonesian health system integration
- `laravel-xendit/` - Payment gateway integration

**Namespace:** `Hanafalah\*`

### Features Directory

Microservice-style feature modules:
- `ms-emr/` - Electronic Medical Records
- `ms-apotek/` - Pharmacy management
- `ms-hr/` - Human Resources
- `ms-scm/` - Supply Chain Management
- `ms-point-of-sale/` - POS system
- `ms-plus-*` - Enhanced versions for wellmed-plus

Each feature module is self-contained with its own controllers, models, and routes.

### Projects Directory

Main application implementations:
- `wellmed-backbone/` - Core API with comprehensive healthcare features
- `hq/` - Headquarters admin panel and workspace management
- `wellmed-lite/` - Simplified version for small clinics
- `wellmed-plus/` - Full-featured version with advanced capabilities
- `wellmed-gateway/` - API gateway for routing requests
- `wellmed-satu-sehat/` - Integration with Indonesian health system

**Namespace:** `Projects\WellmedBackbone\`, `Projects\Hq\`, etc.

## Working with Multiple Applications

### Switching Between Applications

Each application is a separate Docker container with isolated environments:

1. **Backbone** (main API):
   - Container: `wellmed-backbone`
   - Port: 9000
   - Env: `.env.backbone`
   - Composer: `composer_backbone.json`
   - Routes: `routes/api-backbone.php`

2. **HQ** (admin):
   - Container: `wellmed-hq`
   - Port: 9001
   - Env: `.env.hq`
   - Composer: `composer_hq.json`
   - Routes: `routes/api-hq.php`

### Making Changes Across Applications

When modifying shared code in `app/`, `repositories/`, or `features/`:
1. The change affects ALL applications using that code
2. You must reload Octane in each affected container
3. Test in all affected applications to ensure no breakage

When modifying application-specific code in `projects/`:
1. Changes only affect that specific application
2. Only reload Octane in that application's container

## Code Style and Conventions

### API Response Pattern

Uses `Hanafalah\LaravelSupport\Response` for standardized API responses:
- Success responses with data payload
- Error responses with validation details
- Consistent JSON structure across all endpoints

### Middleware

Important middleware to be aware of:
- `LaravelSupportResponse` - Handles API response formatting (prepended globally)
- `OctaneTenantIsolation` - Ensures tenant isolation in Octane (optional but recommended)
- Multi-tenant middleware from `microtenant` package

### Testing Approach

- PHPUnit/Pest for unit and feature tests
- Tests use SQLite in-memory database for speed
- Test environment configured in `phpunit.xml`

## Important Documentation Files

Reference these files for specific topics:
- `OCTANE_FIXES.md` - Memory leaks, heartbeat errors, performance tuning
- `OCTANE_TENANT_ISOLATION.md` - Multi-tenant state management in Octane
- `OCTANE_STATE_FILE_GUIDE.md` - Octane state file debugging
- `POSTGRES_OPTIMIZATION.md` - Database performance tuning
- `PGBOUNCER_IMPLEMENTATION_GUIDE.md` - Connection pooling setup
- `LOCAL_PGBOUNCER_SETUP.md` - Local development with PgBouncer
- `SETUP_COMPARISON.md` - Docker compose configuration comparison

## Common Pitfalls

1. **Forgetting to reload Octane** - Code changes don't appear until reload
2. **Tenant state leakage** - Not properly isolating tenant context between requests
3. **Direct PostgreSQL connection** - Always use PgBouncer (wellmed-pgbouncer) not wellmed-postgres
4. **Wrong container** - Running commands in wrong application container
5. **Static state in Octane** - Storing data in static properties that persists between requests
6. **Missing environment variables** - Each application needs its own .env file

## Health Checks

**Application health endpoint:**
```bash
curl http://localhost:9000/health  # Backbone
curl http://localhost:9001/health  # HQ
```

**Container health:**
```bash
docker-compose -f docker-compose-dev.yaml ps
docker exec wellmed-postgres pg_isready -U postgres
```

**Service status:**
```bash
docker exec -it wellmed-backbone supervisorctl status
# Should show:
# backbone-octane    RUNNING
# octane-monitor     RUNNING
```
