#!/bin/sh
set -e

# Start PHP-FPM in the background
echo "Starting PHP-FPM..."
php-fpm -D

# Start Caddy in the foreground
echo "Starting Caddy..."
exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
