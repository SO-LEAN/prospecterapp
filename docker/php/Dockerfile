# The command line of the PHP apps (tests, Composer, console, PHPStan), on the PHP of their Lambdas (ADR 0055,
# 0056): the dev image of Bref is the PHP of its layer with Xdebug and Composer; the layer of imagick is the one
# of the storage function; make, zip and tar package the api and the storage for AWS (make/lambda.mk).

# the runtimes of docker/lambda and of the layers on AWS (php-85:21 and imagick-php-85:4, apps/*/infra/lambda.tf):
# changed together (ADR 0056)
FROM bref/extra-imagick-php-85:3@sha256:b6ea36cf9e6bdd8e9a73b20dcf2b578eee27a914785ddf28da07624fb48c303d AS imagick

# libfaketime, which no package of Amazon Linux holds: the tests of the core and of the storage a year later
# (make test CLOCK_SHIFT=+365d, ADR 0146)
FROM bref/php-85-dev:3.0.10 AS faketime
ARG FAKETIME_VERSION=0.9.13
ARG FAKETIME_SHA256=8e56deeb805682b025107e095f1d94a6ea677472f05824cd475f5c5e6e1a5ddf
USER root
RUN dnf install -y gcc make tar gzip >/dev/null \
    && curl -fsSL -o /tmp/faketime.tar.gz https://github.com/wolfcw/libfaketime/archive/refs/tags/v${FAKETIME_VERSION}.tar.gz \
    && echo "${FAKETIME_SHA256}  /tmp/faketime.tar.gz" | sha256sum -c - \
    && tar -xzf /tmp/faketime.tar.gz -C /tmp \
    && make -C /tmp/libfaketime-${FAKETIME_VERSION}/src libfaketime.so.1 >/dev/null \
    && install -D /tmp/libfaketime-${FAKETIME_VERSION}/src/libfaketime.so.1 /usr/local/lib/faketime/libfaketime.so.1

FROM bref/php-85-dev:3.0.10

ARG UID=1000
ARG GID=1000

COPY --from=imagick /opt /opt
COPY --from=faketime /usr/local/lib/faketime /usr/local/lib/faketime
# the volume composer-cache, created empty, takes the owner of its mount point: the developer, who writes in it
RUN dnf install -y git unzip zip tar make shadow-utils >/dev/null && dnf clean all \
    && /usr/sbin/groupadd -g ${GID} developer \
    && /usr/sbin/useradd -m -u ${UID} -g ${GID} -s /bin/bash developer \
    && install -d -o developer -g developer /home/developer/.cache/composer

COPY php.ini /opt/bref/etc/php/conf.d/zz-cli.ini

USER developer
WORKDIR /var/www/src
ENTRYPOINT []
CMD ["bash"]
