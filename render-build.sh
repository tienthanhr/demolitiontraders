#!/bin/bash
set -e

echo "Installing PHP PostgreSQL driver..."
apt-get update
apt-get install -y php-pgsql php-pdo

echo "Build completed successfully!"
