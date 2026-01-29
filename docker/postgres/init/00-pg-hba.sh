#!/bin/bash
# Configure pg_hba.conf for MD5 authentication

cat > "$PGDATA/pg_hba.conf" <<EOF
# PostgreSQL Client Authentication Configuration File
# Allow MD5 authentication for PgBouncer compatibility

# TYPE  DATABASE        USER            ADDRESS                 METHOD

# "local" is for Unix domain socket connections only
local   all             all                                     trust

# IPv4 local connections:
host    all             all             127.0.0.1/32            md5
host    all             all             0.0.0.0/0               md5

# IPv6 local connections:
host    all             all             ::1/128                 md5
host    all             all             ::0/0                   md5
EOF

# Reload PostgreSQL configuration
pg_ctl reload
