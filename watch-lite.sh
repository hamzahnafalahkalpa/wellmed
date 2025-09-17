#!/bin/bash

while true; do
  inotifywait -r -e modify --exclude '\.git' .
  echo "🔁 Reloading Octane..."
  docker exec -w /app wellmed_lite php artisan octane:reload
done
