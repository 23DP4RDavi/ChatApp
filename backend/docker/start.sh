#!/bin/sh
set -e

echo "==> Generating nginx config with PORT=${PORT:-8000}..."
PORT=${PORT:-8000}
envsubst '$PORT' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

echo "==> Caching Laravel config/routes/views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Running database migrations..."
php artisan migrate --force

echo "==> Linking storage..."
php artisan storage:link 2>/dev/null || true

echo "==> Starting nginx + php-fpm via supervisord..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
