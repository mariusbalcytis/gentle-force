FROM php:8

ENV PHPREDIS_VERSION 5.3.4

RUN mkdir -p /usr/src/php/ext/redis \
    && curl -L https://github.com/phpredis/phpredis/archive/$PHPREDIS_VERSION.tar.gz | tar xvz -C /usr/src/php/ext/redis --strip 1 \
    && echo 'redis' >> /usr/src/php-available-exts \
    && docker-php-ext-install redis

RUN docker-php-ext-install pcntl
RUN docker-php-ext-install posix
RUN docker-php-ext-install sysvsem
RUN docker-php-ext-install sysvshm
