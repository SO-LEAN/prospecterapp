FROM nginx:1.30-alpine

RUN apk add --no-cache openssl \
    && rm /etc/nginx/conf.d/default.conf

COPY conf.d/ /etc/nginx/conf.d/
COPY templates/ /etc/nginx/templates/
COPY runtime/ /etc/nginx/runtime/
COPY 05-generate-certificate.sh /docker-entrypoint.d/
