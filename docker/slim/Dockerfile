FROM php:8-fpm-alpine

# instalar dependencias
# instalar configuración de php
# preparar montaje para volumen
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    apk del -f .build-deps

RUN docker-php-ext-install mysqli pdo pdo_mysql && \
    ln -s /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini && \
    rm -rf /var/www/html

COPY xdebug.ini /usr/local/etc/php/conf.d/xdebug-config.ini

# ejecutar servidor
CMD [ "php", "-S", "0.0.0.0:80", "-t", "slim", "slim/index.php" ]