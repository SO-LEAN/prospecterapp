# The command line of the PHP apps (tests, Composer, console, PHPStan), on the PHP of their Lambdas (ADR 0055,
# 0056): the dev image of Bref is the PHP of its layer with Xdebug and Composer; the layer of imagick is the one
# of the storage function; make, zip and tar package the api and the storage for AWS (make/lambda.mk).

# the runtimes of docker/lambda and of the layers on AWS (php-85:21 and imagick-php-85:4, apps/*/infra/lambda.tf):
# changed together (ADR 0056)
FROM bref/extra-imagick-php-85:3@sha256:b6ea36cf9e6bdd8e9a73b20dcf2b578eee27a914785ddf28da07624fb48c303d AS imagick

FROM bref/php-85-dev:3.0.10

ARG UID=1000
ARG GID=1000

COPY --from=imagick /opt /opt
RUN dnf install -y git unzip zip tar make shadow-utils >/dev/null && dnf clean all \
    && /usr/sbin/groupadd -g ${GID} developer \
    && /usr/sbin/useradd -m -u ${UID} -g ${GID} -s /bin/bash developer

COPY php.ini /opt/bref/etc/php/conf.d/zz-cli.ini

USER developer
WORKDIR /var/www/src
ENTRYPOINT []
CMD ["bash"]
