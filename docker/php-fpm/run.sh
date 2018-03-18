#!/bin/sh

# Add user defined by DOCKER_USER environment variable
if id -u "$DOCKER_USER" >/dev/null 2>&1; then
    echo "user $DOCKER_USER already exist"
else
    /create-user.sh
fi

# Detect the host IP
export DOCKER_BRIDGE_IP=$(ip ro | grep default | cut -d' ' -f 3)

echo "Launch php fpm"
exec php-fpm
