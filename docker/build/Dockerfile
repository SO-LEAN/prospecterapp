FROM php:7.2.3-cli-alpine
ENV npm_config_unsafe_perm true
#install xdebug in order to use phpcpd "Fatal error: Uncaught Error: Call to undefined function xdebug_disable()"
RUN apk add --no-cache --virtual .my-deps g++ make autoconf &&\
    apk add --no-cache git bash openssh-client nodejs-npm yarn libzip-dev &&\
    npm install npm@5.7.1 n -g && n 9.8.0 &&\
    pecl install xdebug-2.9.8 zip &&\
    docker-php-ext-enable xdebug zip &&\
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin --filename=composer &&\
    curl -L http://cs.sensiolabs.org/download/php-cs-fixer-v2.phar -o /usr/bin/php-cs-fixer && chmod +x /usr/bin/php-cs-fixer &&\
    mkdir /.composer /.npm /.config /.cache && touch /.yarnrc && chmod 777 /.composer /.npm /.config /.cache /.yarnrc

COPY php.ini /usr/local/etc/php/conf.d

WORKDIR /app
