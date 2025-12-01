FROM php:8.2-apache

# Copy toàn bộ project vào thư mục web server
COPY . /var/www/html/

# Bật mod_rewrite cho .htaccess
RUN a2enmod rewrite

# Phân quyền cho Apache
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
