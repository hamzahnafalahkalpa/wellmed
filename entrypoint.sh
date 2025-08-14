#!/bin/bash
# entrypoint.sh
chown -R appuser:appgroup /app/app /app/repositories
exec "$@"