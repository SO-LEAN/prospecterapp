# The command line of the PHP apps (tests, Composer, console, PHPStan): the apps themselves run in their Bref
# Lambdas (ADR 0055)
FROM php:8.5-cli-trixie

ARG UID=1000
ARG GID=1000

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions gd imagick intl opcache zip xdebug

COPY php.ini /usr/local/etc/php/conf.d/zz-app.ini

RUN groupadd -g ${GID} developer \
    && useradd -m -u ${UID} -g ${GID} -s /bin/bash developer

USER developer
WORKDIR /var/www/src
