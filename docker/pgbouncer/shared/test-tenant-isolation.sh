#!/bin/bash

# Tenant Isolation Test Script for PgBouncer
# Tests that search_path is properly reset between transactions to prevent tenant data leakage
# This is CRITICAL for multi-tenant security

set -e

# Configuration
PGHOST="${PGHOST:-localhost}"
PGPORT="${PGPORT:-6432}"
PGUSER="${PGUSER:-postgres}"
PGPASSWORD="${PGPASSWORD:-password123}"
PGDATABASE="${PGDATABASE:-wellmed}"

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "============================================================"
echo "  Tenant Isolation Test for PgBouncer"
echo "============================================================"
echo "Host: $PGHOST:$PGPORT"
echo "Database: $PGDATABASE"
echo "User: $PGUSER"
echo ""

# Test 1: Verify search_path is reset after transaction
echo "Test 1: Verify search_path reset after transaction"
echo "-----------------------------------------------------------"
RESULT=$(PGPASSWORD=$PGPASSWORD psql -h $PGHOST -p $PGPORT -U $PGUSER -d $PGDATABASE -t -A -c "
BEGIN;
SET search_path = 'tenant_test_1';
SELECT current_setting('search_path') as path;
COMMIT;
SELECT current_setting('search_path') as path;
" 2>&1)

if echo "$RESULT" | grep -q "public" || echo "$RESULT" | grep -q '"\$user"'; then
    echo -e "${GREEN}✓ PASS${NC}: search_path was reset to default after transaction"
    echo "  Result: $RESULT"
else
    echo -e "${RED}✗ FAIL${NC}: search_path was NOT reset after transaction"
    echo "  Result: $RESULT"
    echo -e "${YELLOW}  WARNING: This is a CRITICAL security issue!${NC}"
fi
echo ""

# Test 2: Verify DISCARD ALL behavior
echo "Test 2: Verify DISCARD ALL behavior"
echo "-----------------------------------------------------------"
RESULT=$(PGPASSWORD=$PGPASSWORD psql -h $PGHOST -p $PGPORT -U $PGUSER -d $PGDATABASE -t -A -c "
BEGIN;
SET search_path = 'tenant_test_2';
PREPARE test_stmt AS SELECT 1;
SET application_name = 'test_app';
COMMIT;
-- After transaction, prepared statements should be discarded
SELECT count(*) FROM pg_prepared_statements WHERE name = 'test_stmt';
" 2>&1)

if echo "$RESULT" | grep -q "^0$"; then
    echo -e "${GREEN}✓ PASS${NC}: DISCARD ALL working - prepared statements cleared"
    echo "  Prepared statements count after COMMIT: 0"
else
    echo -e "${RED}✗ FAIL${NC}: DISCARD ALL not working properly"
    echo "  Prepared statements count: $RESULT"
fi
echo ""

# Test 3: Concurrent tenant access isolation
echo "Test 3: Concurrent tenant access isolation"
echo "-----------------------------------------------------------"
echo "Running 5 concurrent connections with different tenant schemas..."

TEMP_DIR=$(mktemp -d)
SUCCESS_COUNT=0

for i in {1..5}; do
  (
    RESULT=$(PGPASSWORD=$PGPASSWORD psql -h $PGHOST -p $PGPORT -U $PGUSER -d $PGDATABASE -t -A -c "
      BEGIN;
      SET search_path = 'tenant_clinic_$i';
      SELECT pg_sleep(0.1);
      SELECT current_setting('search_path') as path;
      COMMIT;
    " 2>&1)

    if echo "$RESULT" | grep -q "tenant_clinic_$i"; then
      echo "1" > "$TEMP_DIR/success_$i"
    else
      echo "0" > "$TEMP_DIR/success_$i"
    fi
  ) &
done

wait

for i in {1..5}; do
  if [ -f "$TEMP_DIR/success_$i" ] && [ "$(cat "$TEMP_DIR/success_$i")" = "1" ]; then
    ((SUCCESS_COUNT++))
  fi
done

rm -rf "$TEMP_DIR"

if [ $SUCCESS_COUNT -eq 5 ]; then
    echo -e "${GREEN}✓ PASS${NC}: All 5 concurrent connections maintained correct search_path"
    echo "  Success rate: 5/5 (100%)"
else
    echo -e "${RED}✗ FAIL${NC}: Some concurrent connections had incorrect search_path"
    echo "  Success rate: $SUCCESS_COUNT/5"
fi
echo ""

# Test 4: Verify pool mode is transaction
echo "Test 4: Verify pool mode is transaction"
echo "-----------------------------------------------------------"
RESULT=$(PGPASSWORD=$PGPASSWORD psql -h $PGHOST -p $PGPORT -U $PGUSER -d pgbouncer -t -A -c "
SHOW CONFIG;
" 2>&1 | grep "pool_mode")

if echo "$RESULT" | grep -q "transaction"; then
    echo -e "${GREEN}✓ PASS${NC}: Pool mode is set to 'transaction'"
    echo "  $RESULT"
else
    echo -e "${YELLOW}⚠ WARNING${NC}: Pool mode is not set to 'transaction'"
    echo "  $RESULT"
    echo "  This may cause issues with tenant isolation"
fi
echo ""

# Test 5: Verify server_reset_query is DISCARD ALL
echo "Test 5: Verify server_reset_query is DISCARD ALL"
echo "-----------------------------------------------------------"
RESULT=$(PGPASSWORD=$PGPASSWORD psql -h $PGHOST -p $PGPORT -U $PGUSER -d pgbouncer -t -A -c "
SHOW CONFIG;
" 2>&1 | grep "server_reset_query")

if echo "$RESULT" | grep -q "DISCARD ALL"; then
    echo -e "${GREEN}✓ PASS${NC}: server_reset_query is set to 'DISCARD ALL'"
    echo "  $RESULT"
else
    echo -e "${RED}✗ FAIL${NC}: server_reset_query is not set correctly"
    echo "  $RESULT"
    echo -e "${YELLOW}  CRITICAL: This will cause tenant data leakage!${NC}"
fi
echo ""

# Summary
echo "============================================================"
echo "  Test Summary"
echo "============================================================"
echo "Environment: $PGHOST:$PGPORT"
echo "All critical tests must pass for production deployment."
echo ""
echo "Next steps:"
echo "1. If any tests failed, review pgbouncer.ini configuration"
echo "2. Ensure 'pool_mode = transaction' is set"
echo "3. Ensure 'server_reset_query = DISCARD ALL' is set"
echo "4. Test in each environment (local, dev, staging, prod)"
echo "============================================================"
