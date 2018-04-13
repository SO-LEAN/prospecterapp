FROM php:7.2.3-cli-alpine

RUN apk update &&\
    apk add --no-cache --virtual .my-deps g++ make autoconf &&\
    apk add --no-cache git bash openssh-client nodejs-npm yarn &&\
    npm install npm@5.7.1 n -g && n 9.8.0 &&\
    pecl install xdebug &&\
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer &&\
    curl -L http://cs.sensiolabs.org/download/php-cs-fixer-v2.phar -o /usr/bin/php-cs-fixer && chmod +x /usr/bin/php-cs-fixer &&\
    mkdir /.composer /.npm /.config /.cache && touch /.yarnrc && chmod 777 /.composer /.npm /.config /.cache /.yarnrc

COPY php.ini /usr/local/etc/php/conf.d

WORKDIR /app
