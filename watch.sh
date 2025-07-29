#!/bin/bash

while true; do
  inotifywait -r -e modify --exclude '\.git' .
  echo "🔁 Reloading Octane..."
  docker exec -w /app klinik php artisan octane:reload
done
