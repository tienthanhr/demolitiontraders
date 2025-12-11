FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

# Enable required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql gd zip

# Ensure only a single MPM is enabled (prefork for mod_php) and enable Apache modules
RUN a2dismod mpm_event mpm_worker || true \
    && a2enmod mpm_prefork rewrite \
    && sed -ri 's#/var/www/html#/var/www/html#g' /etc/apache2/sites-available/000-default.conf \
    && printf "<Directory /var/www/html>\n    AllowOverride All\n    Require all granted\n</Directory>\n" > /etc/apache2/conf-available/override.conf \
    && a2enconf override

# Copy custom PHP config (e.g., extensions.ini/php.ini) if present
COPY conf.d/*.ini /usr/local/etc/php/conf.d/

# Copy application code
COPY . /var/www/html/

WORKDIR /var/www/html

# Expose port 80 (Railway will map to $PORT)
EXPOSE 80

# Verify Apache configuration during build (fail fast if misconfigured)
RUN apache2ctl -t

CMD ["apache2-foreground"]
