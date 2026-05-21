#!/bin/sh
set -e

PORT=${PORT:-8080}
echo "==> Starting Laravel Reverb WebSocket server on port $PORT..."
exec php artisan reverb:start --host=0.0.0.0 --port="$PORT" --no-interaction
