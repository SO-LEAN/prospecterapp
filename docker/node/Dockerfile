FROM node:24-alpine

RUN npm install --global pnpm@12.11.2 \
    && mkdir -p /pnpm-store /tmp/home && chmod 777 /pnpm-store /tmp/home

ENV npm_config_store_dir=/pnpm-store \
    pnpm_config_store_dir=/pnpm-store \
    HOME=/tmp/home

WORKDIR /var/www/src/apps/front
