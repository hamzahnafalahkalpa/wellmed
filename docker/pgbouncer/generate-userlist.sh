#!/bin/bash
# Generate PgBouncer userlist from PostgreSQL database
# Usage: ./generate-userlist.sh <postgres_host> <postgres_port> <postgres_db> <postgres_user> <postgres_password>

set -e

PGHOST="${1:-wellmed_postgres}"
PGPORT="${2:-5432}"
PGDATABASE="${3:-wellmed}"
PGUSER="${4:-postgres}"
PGPASSWORD="${5:-password123}"

export PGPASSWORD

echo "Generating userlist from PostgreSQL..."

# Query PostgreSQL for users and passwords
psql -h "$PGHOST" -p "$PGPORT" -U "$PGUSER" -d "$PGDATABASE" -t -A -c \
  "SELECT '\"' || usename || '\" \"' || passwd || '\"'
   FROM pg_shadow
   WHERE passwd IS NOT NULL;" > /etc/pgbouncer/userlist.txt

echo "Userlist generated successfully!"
cat /etc/pgbouncer/userlist.txt
