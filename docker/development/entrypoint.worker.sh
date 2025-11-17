#!/bin/bash

echo "Starting Supervisor for Laravel worker..."

sleep 2

exec /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
