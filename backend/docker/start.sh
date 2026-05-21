#!/bin/sh
set -eu

PORT="${PORT:-8080}"

echo "==> Clearing bootstrap cache..."
rm -f /var/www/bootstrap/cache/*.php

# Some Railway services end up with a domain target port that differs from $PORT.
# Serve both ports so proxy routing succeeds regardless of current target setting.
if [ "${PORT}" != "8000" ]; then
	echo "==> Starting fallback PHP server on port 8000..."
	php -S 0.0.0.0:8000 -t /var/www/public /var/www/public/index.php >/tmp/php-8000.log 2>&1 &
fi

echo "==> Starting PHP built-in server on port ${PORT}..."
exec php -S 0.0.0.0:${PORT} -t /var/www/public /var/www/public/index.php
