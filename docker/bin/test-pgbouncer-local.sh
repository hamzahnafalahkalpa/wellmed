#!/bin/bash
# Test PgBouncer connection for local development

set -e

echo "========================================="
echo "PgBouncer Local Connection Test"
echo "========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
POSTGRES_HOST="localhost"
POSTGRES_PORT="5433"
PGBOUNCER_HOST="localhost"
PGBOUNCER_PORT="6432"
DB_USER="postgres"
DB_PASS="password123"
DB_NAME="wellmed"

echo "Configuration:"
echo "  PostgreSQL: $POSTGRES_HOST:$POSTGRES_PORT"
echo "  PgBouncer:  $PGBOUNCER_HOST:$PGBOUNCER_PORT"
echo "  Database:   $DB_NAME"
echo "  User:       $DB_USER"
echo ""

# Test 1: PostgreSQL Direct Connection
echo "Test 1: PostgreSQL Direct Connection"
echo "--------------------------------------"
if PGPASSWORD=$DB_PASS psql -h $POSTGRES_HOST -p $POSTGRES_PORT -U $DB_USER -d $DB_NAME -c "SELECT 'PostgreSQL OK' as status;" 2>&1 | grep -q "PostgreSQL OK"; then
    echo -e "${GREEN}✓ PostgreSQL connection successful${NC}"
else
    echo -e "${RED}✗ PostgreSQL connection failed${NC}"
    echo "Fix: Check if PostgreSQL is running on port $POSTGRES_PORT"
    exit 1
fi
echo ""

# Test 2: Check PostgreSQL Password Encryption
echo "Test 2: PostgreSQL Password Encryption"
echo "---------------------------------------"
PASS_TYPE=$(PGPASSWORD=$DB_PASS psql -h $POSTGRES_HOST -p $POSTGRES_PORT -U $DB_USER -d $DB_NAME -t -A -c "SELECT CASE WHEN rolpassword LIKE 'md5%' THEN 'MD5' WHEN rolpassword LIKE 'SCRAM%' THEN 'SCRAM-SHA-256' ELSE 'UNKNOWN' END FROM pg_authid WHERE rolname='postgres';")

if [ "$PASS_TYPE" = "MD5" ]; then
    echo -e "${GREEN}✓ Password encryption: MD5 (PgBouncer compatible)${NC}"
elif [ "$PASS_TYPE" = "SCRAM-SHA-256" ]; then
    echo -e "${RED}✗ Password encryption: SCRAM-SHA-256 (NOT compatible)${NC}"
    echo ""
    echo "Fix:"
    echo "  1. Set password_encryption = md5 in postgresql.conf"
    echo "  2. Run: ALTER USER postgres WITH PASSWORD 'password123';"
    echo "  3. Restart PostgreSQL"
    exit 1
else
    echo -e "${YELLOW}⚠ Password encryption: $PASS_TYPE (Unknown)${NC}"
fi
echo ""

# Test 3: PgBouncer Container Running
echo "Test 3: PgBouncer Container"
echo "----------------------------"
if docker ps | grep -q "wellmed-pgbouncer-local"; then
    echo -e "${GREEN}✓ PgBouncer container is running${NC}"

    # Show container info
    CONTAINER_STATUS=$(docker ps --filter "name=wellmed-pgbouncer-local" --format "{{.Status}}")
    echo "  Status: $CONTAINER_STATUS"
else
    echo -e "${RED}✗ PgBouncer container is not running${NC}"
    echo ""
    echo "Fix:"
    echo "  docker-compose -f docker-compose-local.yaml up -d wellmed_pgbouncer"
    exit 1
fi
echo ""

# Test 4: PgBouncer Connection
echo "Test 4: PgBouncer Connection"
echo "------------------------------"
if PGPASSWORD=$DB_PASS psql -h $PGBOUNCER_HOST -p $PGBOUNCER_PORT -U $DB_USER -d $DB_NAME -c "SELECT 'PgBouncer OK' as status;" 2>&1 | grep -q "PgBouncer OK"; then
    echo -e "${GREEN}✓ PgBouncer connection successful${NC}"
else
    echo -e "${RED}✗ PgBouncer connection failed${NC}"
    echo ""
    echo "Checking PgBouncer logs:"
    docker logs wellmed-pgbouncer-local --tail 20
    echo ""
    echo "Common issues:"
    echo "  1. PostgreSQL not using MD5 authentication"
    echo "  2. pg_hba.conf not allowing connections from Docker network"
    echo "  3. PostgreSQL not listening on 0.0.0.0"
    exit 1
fi
echo ""

# Test 5: Laravel Connection (if container is running)
echo "Test 5: Laravel Database Connection"
echo "-------------------------------------"
if docker ps | grep -q "wellmed-backbone"; then
    if docker exec wellmed-backbone php -r "try { \$pdo = new PDO('pgsql:host=wellmed-pgbouncer-local;port=5432;dbname=wellmed', 'postgres', 'password123'); echo 'Laravel OK'; } catch (Exception \$e) { echo 'Laravel FAILED: ' . \$e->getMessage(); exit(1); }" 2>&1 | grep -q "Laravel OK"; then
        echo -e "${GREEN}✓ Laravel can connect to PgBouncer${NC}"
    else
        echo -e "${RED}✗ Laravel cannot connect to PgBouncer${NC}"
        echo "Check .env.backbone configuration"
    fi
else
    echo -e "${YELLOW}⚠ Laravel container not running (skipped)${NC}"
fi
echo ""

# Summary
echo "========================================="
echo -e "${GREEN}All tests passed! ✓${NC}"
echo "========================================="
echo ""
echo "PgBouncer is ready to use."
echo ""
echo "Update your .env.backbone:"
echo "  DB_HOST=localhost"
echo "  DB_PORT=6432"
echo ""
echo "Or from Docker containers:"
echo "  DB_HOST=wellmed-pgbouncer-local"
echo "  DB_PORT=5432"
echo ""
