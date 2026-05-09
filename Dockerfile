FROM php:8.1-apache
RUN docker-php-ext-install pdo pdo_mysql
RUN a2enmod rewrite

# Allow larger uploads (Canvas-compressed photos ≤ 5MB)
RUN { \
    echo 'upload_max_filesize = 8M'; \
    echo 'post_max_size = 12M'; \
    echo 'memory_limit = 128M'; \
  } > /usr/local/etc/php/conf.d/uploads.ini

# Ensure uploads dir exists & writable by Apache user (UID 33 / www-data)
RUN mkdir -p /var/www/html/uploads && chown -R www-data:www-data /var/www/html/uploads && chmod -R 775 /var/www/html/uploads
