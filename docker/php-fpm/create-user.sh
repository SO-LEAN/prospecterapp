#!/bin/sh

addgroup -g $GID $DOCKER_USER
adduser -D -u $UID -G $DOCKER_USER -s /bin/sh $DOCKER_USER