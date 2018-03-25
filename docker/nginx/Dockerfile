FROM nginx:1.12.0-alpine

ENV NGINX_ERROR_LOG=/dev/fd/2 \
    NGINX_ACCESS_LOG=/dev/null \
    CONTAINER_NAME=php-fpm

RUN rm /etc/nginx/conf.d/default.conf

COPY ["nginx.conf", "project.conf", "static.conf", "/etc/nginx/templates/"]
COPY "run.sh" "/"

CMD "/run.sh"
