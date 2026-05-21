#!/bin/sh
set -eu

PORT="${PORT:-8080}"

echo "==> Clearing bootstrap cache..."
rm -f /var/www/bootstrap/cache/*.php

echo "==> Starting PHP built-in server on port ${PORT}..."
# Avoid artisan during container boot to prevent stale provider discovery failures.
exec php -S 0.0.0.0:${PORT} -t /var/www/public /var/www/public/index.php
