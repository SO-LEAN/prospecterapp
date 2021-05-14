#!/bin/sh

FILE="/etc/ssl/certs/project.crt"

if [ -f "$FILE" ]
 then
    echo "certificate already generated"
 else
    echo "generating certificate"
    openssl req -x509 -nodes -days 365 -newkey rsa:1024 \
        -keyout /etc/ssl/certs/project.key \
        -out /etc/ssl/certs/project.crt \
        -subj "/C=EN/L=London/O=App Project/OU=Development/CN=*.$NGINX_HOST"
fi

envsubst \$NGINX_ERROR_LOG,\$NGINX_ACCESS_LOG < /etc/nginx/templates/nginx.conf > /etc/nginx/nginx.conf
envsubst \$CONTAINER_NAME,\$NGINX_HOST,\$NGINX_PROJECT < /etc/nginx/templates/project.conf > /etc/nginx/conf.d/project.conf
envsubst \$CONTAINER_NAME,\$NGINX_HOST < /etc/nginx/templates/static.conf > /etc/nginx/conf.d/static.conf

nginx -g 'daemon off;'
