#!/bin/sh
set -e

echo "Starting deployment script..."
echo "PORT environment variable is: $PORT"

# Configure PHP-FPM to listen on 127.0.0.1:9000
# The default config usually listens on 9000 but let's be sure about the bind address
# sed -i 's/listen = 127.0.0.1:9000/listen = 127.0.0.1:9000/g' /usr/local/etc/php-fpm.d/www.conf
# sed -i 's/listen = 9000/listen = 127.0.0.1:9000/g' /usr/local/etc/php-fpm.d/www.conf

echo "Starting PHP-FPM..."
# Start PHP-FPM in background
php-fpm -D

# Wait a moment for PHP-FPM to start
sleep 2

echo "Starting Caddy on port $PORT..."
# Run Caddy in foreground
exec caddy run --config /etc/caddy/Caddyfile --adapter caddyfile
