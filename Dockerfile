FROM php:8.2-apache

# Enable required PHP extensions
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache modules and allow .htaccess overrides
RUN a2enmod rewrite \
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

CMD ["apache2-foreground"]
