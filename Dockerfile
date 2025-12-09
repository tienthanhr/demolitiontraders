# Use PHP 8.2 FPM as the base image
FROM php:8.2-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libpq-dev \
    unzip \
    git \
    debian-keyring \
    debian-archive-keyring \
    apt-transport-https \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql mysqli zip pdo_pgsql pgsql

# Install Caddy
RUN curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/gpg.key' | gpg --dearmor -o /usr/share/keyrings/caddy-stable-archive-keyring.gpg \
    && curl -1sLf 'https://dl.cloudsmith.io/public/caddy/stable/debian.deb.txt' | tee /etc/apt/sources.list.d/caddy-stable.list \
    && apt-get update \
    && apt-get install -y caddy

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Copy Caddyfile to default location
COPY Caddyfile /etc/caddy/Caddyfile

# Copy startup script
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

# Ensure permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port (handled by Railway)
ENV PORT=8080
EXPOSE 8080

# Use start script
CMD ["/usr/local/bin/start.sh"]
