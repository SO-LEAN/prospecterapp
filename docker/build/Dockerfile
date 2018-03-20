FROM php:7.2.3-cli-alpine

RUN apk update &&\
    apk add --no-cache make autoconf automake g++ nasm zlib zlib-dev git bash openssh-client nodejs-npm &&\
    npm install npm@5.7.1 n -g && n 9.8.0 &&\
    pecl install xdebug &&\
    docker-php-ext-enable xdebug && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer && \
    mkdir /.composer /.npm /.config && chmod 777 /.composer /.npm /.config

COPY php.ini /usr/local/etc/php/conf.d

WORKDIR /app