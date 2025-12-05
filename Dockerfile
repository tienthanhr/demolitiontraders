FROM php:8.2-apache

# Install PostgreSQL and MySQL PDO drivers
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Create session storage directory
RUN mkdir -p /var/lib/php/sessions && \
    chown www-data:www-data /var/lib/php/sessions && \
    chmod 1733 /var/lib/php/sessions

# Create logs directory
RUN mkdir -p /var/www/html/backend/logs && \
    chown www-data:www-data /var/www/html/backend/logs

# Copy toàn bộ project vào thư mục web server
COPY . /var/www/html/

# Copy Apache configuration
COPY apache-config.conf /etc/apache2/conf-available/demolition-traders.conf
RUN a2enconf demolition-traders

# Bật mod_rewrite cho .htaccess
RUN a2enmod rewrite

# Phân quyền cho Apache
RUN chown -R www-data:www-data /var/www/html

# Configure PHP for production
RUN echo "session.save_path = '/var/lib/php/sessions'" >> /usr/local/etc/php/conf.d/session.ini && \
    echo "upload_max_filesize = 100M" >> /usr/local/etc/php/conf.d/upload.ini && \
    echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/upload.ini && \
    echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/memory.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/timeout.ini

EXPOSE 80
