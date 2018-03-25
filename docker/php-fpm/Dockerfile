FROM php:7.2.3-fpm-alpine3.7

ENV DOCKER_USER=developer

ADD php-fpm.conf /usr/local/etc/php-fpm.conf
ADD ["create-user.sh", "run.sh", "/"]

CMD "/run.sh"