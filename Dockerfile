FROM php:8.1-apache
RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite headers

# 讓 .htaccess 的 Header / FilesMatch 等指令生效
RUN sed -i 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf

# Allow larger uploads (Canvas-compressed photos ≤ 5MB)
RUN { \
    echo 'upload_max_filesize = 8M'; \
    echo 'post_max_size = 12M'; \
    echo 'memory_limit = 128M'; \
  } > /usr/local/etc/php/conf.d/uploads.ini

# Ensure uploads dir exists & writable by Apache user (UID 33 / www-data)
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads && chmod -R 775 /var/www/html/uploads
