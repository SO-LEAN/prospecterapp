FROM node:24-alpine

# the install scripts of pnpm allowed (npm runs none it was not told of), without the notice of a new npm
RUN npm install --global --no-update-notifier --allow-scripts=pnpm pnpm@12.11.2 \
    && mkdir -p /pnpm-store /tmp/home && chmod 777 /pnpm-store /tmp/home

# libfaketime, where the PHP image has it: the tests of the front a year later (make test CLOCK_SHIFT=+365d, ADR 0150)
RUN apk add --no-cache libfaketime \
    && mkdir -p /usr/local/lib/faketime && ln -s /usr/lib/faketime/libfaketime.so.1 /usr/local/lib/faketime/libfaketime.so.1

ENV npm_config_store_dir=/pnpm-store \
    pnpm_config_store_dir=/pnpm-store \
    HOME=/tmp/home

WORKDIR /var/www/src/apps/front
