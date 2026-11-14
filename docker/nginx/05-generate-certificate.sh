#!/bin/sh
# Serves the certificate the local authority of the machine signed (make ca), in the stack of every checkout, when
# there is one; otherwise a self-signed certificate of the checkout, generated on first start and again when its key
# is missing: a worktree may get the certificate of the main checkout without the key, written by root (ADR 0116).
set -e

SIGNED=/etc/ssl/signed
SELF_SIGNED=/etc/ssl/certs
SERVED=/etc/ssl/served

serve() {
    mkdir -p "$SERVED"
    ln -sf "$1/project.crt" "$SERVED/project.crt"
    ln -sf "$1/project.key" "$SERVED/project.key"
}

generate_self_signed() {
    echo "generating certificate for $NGINX_HOST, api.$NGINX_HOST and static.$NGINX_HOST"
    openssl req -x509 -nodes -days 825 -newkey rsa:2048 -sha256 \
        -keyout "$SELF_SIGNED/project.key" -out "$SELF_SIGNED/project.crt" \
        -subj "/O=App Project/OU=Development/CN=$NGINX_HOST" \
        -addext "subjectAltName=DNS:$NGINX_HOST,DNS:api.$NGINX_HOST,DNS:static.$NGINX_HOST"
}

if [ -f "$SIGNED/project.crt" ] && [ -f "$SIGNED/project.key" ]; then
    echo "serving the certificate of the local authority"
    serve "$SIGNED"
    exit 0
fi

if [ -f "$SELF_SIGNED/project.crt" ] && [ -f "$SELF_SIGNED/project.key" ]; then
    echo "certificate already generated"
else
    generate_self_signed
fi
serve "$SELF_SIGNED"
