#!/bin/sh

envsubst \$NGINX_ERROR_LOG,\$NGINX_ACCESS_LOG < /etc/nginx/templates/nginx.conf > /etc/nginx/nginx.conf
envsubst \$CONTAINER_NAME,\$NGINX_HOST < /etc/nginx/templates/project.conf > /etc/nginx/conf.d/project.conf
envsubst \$CONTAINER_NAME,\$NGINX_STATIC_HOST < /etc/nginx/templates/static.conf > /etc/nginx/conf.d/static.conf

nginx -g 'daemon off;'
