FROM php:7.2-fpm-alpine

RUN apk add --update \
    make \
    bash \
    curl \
    libzip-dev

RUN docker-php-ext-install zip pcntl

RUN rm -rf /var/cache/apk/* && rm -rf /tmp/*

RUN curl --insecure https://getcomposer.org/composer.phar -o /usr/bin/composer && chmod +x /usr/bin/composer

COPY docker/php-fpm/symfony.pool.conf /usr/local/etc/php-fpm.d/

CMD ["php-fpm", "-F"]

COPY . /var/www/symfony

RUN cd /var/www/symfony ; /usr/bin/composer install

WORKDIR /var/www/symfony
EXPOSE 9000
