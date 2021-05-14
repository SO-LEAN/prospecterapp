FROM php:7.2.3-fpm-alpine3.7

ARG UID
ARG GID
ENV DOCKER_USER developer

COPY php-fpm.conf /usr/local/etc/php-fpm.conf

RUN \
    apk add --update --no-cache gcc g++ autoconf libtool make imagemagick imagemagick-dev freetype libpng libjpeg-turbo freetype-dev libpng-dev libjpeg-turbo-dev && \
    docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ --with-png-dir=/usr/include/ && \
    docker-php-ext-install -j$(nproc) gd &&\
    pecl install imagick && \
    docker-php-ext-enable imagick && \
    apk del --no-cache freetype-dev libpng-dev libjpeg-turbo-dev imagemagick-dev gcc g++ libtool make autoconf && \
    addgroup -g $GID $DOCKER_USER && \
    adduser -D -u $UID -G $DOCKER_USER -s /bin/sh $DOCKER_USER

USER $DOCKER_USER
