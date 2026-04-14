FROM php:8-fpm-alpine

# instalar dependencias
# instalar configuración de php
# preparar montaje para volumen
RUN docker-php-ext-install mysqli pdo pdo_mysql && \
    ln -s /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
    rm -rf /var/www/html

# ejecutar servidor
CMD [ "php", "-S", "0.0.0.0:80", "-t", "slim", "slim/index.php" ]