#!/bin/bash
# Install PHP PostgreSQL extensions for Render
apt-get update
apt-get install -y php-pgsql php-pdo

# Restart Apache to load new extensions
apachectl restart
