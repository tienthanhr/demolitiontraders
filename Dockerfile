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


# Remove ALL MPM symlinks except mpm_prefork, then enable mpm_prefork and rewrite
RUN rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf || true \
    && a2enmod mpm_prefork rewrite \
    && echo "MPM symlinks after cleanup:" \
    && ls -la /etc/apache2/mods-enabled | grep mpm_ || true \
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


# Thêm cấu hình ServerName để xử lý cảnh báo
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Entrypoint: Xóa toàn bộ symlink MPM, bật lại mpm_prefork, in ra danh sách MPM đã bật trước khi start Apache
ENTRYPOINT bash -c "\
    rm -f /etc/apache2/mods-enabled/mpm_*.load /etc/apache2/mods-enabled/mpm_*.conf; \
    a2enmod mpm_prefork; \
    echo 'Enabled MPM modules:'; \
    ls -la /etc/apache2/mods-enabled | grep mpm_ || true; \
    a2query -M || true; \
    apache2-foreground \
"
