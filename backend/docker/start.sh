#!/bin/sh
set -eu

PORT="${PORT:-8080}"

echo "==> Clearing bootstrap cache..."
rm -f /var/www/bootstrap/cache/*.php

# Start fallback listeners on common Railway target ports in case the domain target
# is not aligned with the runtime PORT value.
for p in 8000 8080 9000; do
	if [ "$p" != "$PORT" ]; then
		echo "==> Starting fallback PHP server on port $p..."
		php -S 0.0.0.0:$p -t /var/www/public /var/www/public/index.php >/tmp/php-$p.log 2>&1 &
	fi
done

echo "==> Starting primary PHP server on port ${PORT}..."
exec php -S 0.0.0.0:${PORT} -t /var/www/public /var/www/public/index.php
