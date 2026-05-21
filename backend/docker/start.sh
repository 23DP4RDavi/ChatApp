#!/bin/sh
# Do NOT use set -e — allow the container to start even if migrations fail

echo "==> Generating nginx config with PORT=${PORT:-8000}..."
PORT=${PORT:-8000}
envsubst '$PORT' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

echo "==> Clearing bootstrap cache..."
rm -f /var/www/bootstrap/cache/*.php

echo "==> Discovering packages..."
php artisan package:discover --ansi || true

echo "==> Caching Laravel config/routes/views..."
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "==> Running database migrations..."
php artisan migrate --force || echo "[WARN] Migrations failed — check DB env vars"

echo "==> Linking storage..."
php artisan storage:link 2>/dev/null || true

echo "==> Starting nginx + php-fpm via supervisord..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
