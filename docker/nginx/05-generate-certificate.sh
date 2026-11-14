#!/bin/sh
# Generates a self-signed certificate for the SPA, API and static hosts on first start, and again when its key is
# missing: a worktree may get the certificate of the main checkout without the key, written by root (ADR 0116).
set -e

CRT=/etc/ssl/certs/project.crt
KEY=/etc/ssl/certs/project.key

if [ -f "$CRT" ] && [ -f "$KEY" ]; then
    echo "certificate already generated"
    exit 0
fi

echo "generating certificate for $NGINX_HOST, api.$NGINX_HOST and static.$NGINX_HOST"
openssl req -x509 -nodes -days 825 -newkey rsa:2048 -sha256 \
    -keyout "$KEY" -out "$CRT" \
    -subj "/O=App Project/OU=Development/CN=$NGINX_HOST" \
    -addext "subjectAltName=DNS:$NGINX_HOST,DNS:api.$NGINX_HOST,DNS:static.$NGINX_HOST"
