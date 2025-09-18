#!/bin/bash

while true; do
  inotifywait -r -e modify --exclude '\.git' .
  echo "🔁 Reloading Octane..."
  docker exec -w /app wellmed_hq php artisan octane:reload
done
