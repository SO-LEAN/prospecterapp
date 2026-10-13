#!/bin/sh

FILE="/etc/ssl/certs/project.crt"

if [ -f "$FILE" ]
 then
    echo "certificate already generated"
 else
    echo "generating certificate"
    cat > /tmp/openssl.cnf <<EOF
[req]
distinguished_name = dn
x509_extensions = v3
prompt = no
[dn]
C = FR
O = App Project
OU = Development
CN = $NGINX_HOST
[v3]
subjectAltName = DNS:$NGINX_HOST,DNS:static.$NGINX_HOST
basicConstraints = critical,CA:FALSE
keyUsage = digitalSignature,keyEncipherment
extendedKeyUsage = serverAuth
EOF
    openssl req -x509 -nodes -days 825 -newkey rsa:2048 -sha256 \
        -config /tmp/openssl.cnf \
        -keyout /etc/ssl/certs/project.key \
        -out /etc/ssl/certs/project.crt
fi

envsubst \$NGINX_ERROR_LOG,\$NGINX_ACCESS_LOG < /etc/nginx/templates/nginx.conf > /etc/nginx/nginx.conf
envsubst \$CONTAINER_NAME,\$NGINX_HOST,\$NGINX_PROJECT < /etc/nginx/templates/project.conf > /etc/nginx/conf.d/project.conf
envsubst \$CONTAINER_NAME,\$NGINX_HOST < /etc/nginx/templates/static.conf > /etc/nginx/conf.d/static.conf

nginx -g 'daemon off;'
