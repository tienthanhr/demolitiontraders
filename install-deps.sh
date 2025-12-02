#!/bin/bash
# Install PHP MySQL extensions
apt-get update
apt-get install -y php-mysql php-pdo php-pdo-mysql

# Restart Apache to load new extensions
apachectl restart
