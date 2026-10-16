# Deploys the API and the storage to MiniStack as Lambdas with the layers of Bref (ADR 0053, 0054): a layer is
# the /opt of a Bref image, the code of a function only the local bootstrap, the repository being mounted in it.
FROM bref/php-85:3.0.9 AS php
FROM bref/extra-imagick-php-85:3.0.5 AS imagick

FROM amazon/aws-cli:2.37.2
RUN yum install -y -q zip && yum clean all
# the layers of the images above, named after their version
ENV PHP_LAYER=bref-php-85-3-0-9 \
    IMAGICK_LAYER=bref-imagick-php-85-3-0-5
COPY --from=php /opt /build/php
COPY --from=imagick /opt /build/imagick
COPY bootstrap /build/function/bootstrap
RUN chmod +x /build/function/bootstrap \
    && cd /build/php && zip -q -r -y /build/php.zip . \
    && cd /build/imagick && zip -q -r -y /build/imagick.zip . \
    && cd /build/function && zip -q /build/function.zip bootstrap
COPY deploy.sh /usr/local/bin/deploy
ENTRYPOINT ["/usr/local/bin/deploy"]
