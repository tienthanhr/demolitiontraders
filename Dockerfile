FROM php:8.2-apache

# Copy toàn bộ mã nguồn vào thư mục web server
COPY demolitiontraders/ /var/www/html/

# Phân quyền cho Apache
RUN chown -R www-data:www-data /var/www/html

# Nếu sau này bạn dùng .htaccess → bật rewrite
RUN a2enmod rewrite

# Mở port 80 cho web
EXPOSE 80
