# The command line of the PHP apps (tests, Composer, console, PHPStan), on the PHP of their Lambdas (ADR 0055,
# 0056): the dev image of Bref is the PHP of its layer with Xdebug and Composer; the layer of imagick is the one
# of the storage function; zip and tar package the api for AWS (tools/package/api).
FROM bref/extra-imagick-php-85:3.0.5 AS imagick

# the runtime of docker/lambda and of the layer on AWS (php-85:20, apps/api/infra/lambda.tf): changed together (ADR 0056)
FROM bref/php-85-dev:3.0.9

ARG UID=1000
ARG GID=1000

COPY --from=imagick /opt /opt
RUN dnf install -y git unzip zip tar shadow-utils >/dev/null && dnf clean all \
    && /usr/sbin/groupadd -g ${GID} developer \
    && /usr/sbin/useradd -m -u ${UID} -g ${GID} -s /bin/bash developer

COPY php.ini /opt/bref/etc/php/conf.d/zz-cli.ini

USER developer
WORKDIR /var/www/src
ENTRYPOINT []
CMD ["bash"]
