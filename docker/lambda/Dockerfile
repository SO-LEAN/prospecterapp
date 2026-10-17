# Deploys the API and the storage to MiniStack as Lambdas with the layers of Bref (ADR 0053, 0054): a layer is
# the /opt of a Bref image, the code of a function only the local bootstrap, the repository being mounted in it.
# The runtime of Bref is the one of the layer on AWS (bref/php-85:3.0.9 = layer php-85:20, apps/*/infra/lambda.tf)
# and of docker/php, imagick too (the image of this digest = layer imagick-php-85:4, published without a version
# tag): a new version of Bref changes the three (ADR 0056).
FROM bref/php-85:3.0.9 AS php
FROM bref/extra-imagick-php-85:3@sha256:b6ea36cf9e6bdd8e9a73b20dcf2b578eee27a914785ddf28da07624fb48c303d AS imagick

FROM amazon/aws-cli:2.37.2
RUN yum install -y -q zip && yum clean all
# the layers of the images above, named after their version
ENV PHP_LAYER=bref-php-85-3-0-9 \
    IMAGICK_LAYER=bref-imagick-php-85-4
COPY --from=php /opt /build/php
COPY --from=imagick /opt /build/imagick
COPY bootstrap /build/function/bootstrap
RUN chmod +x /build/function/bootstrap \
    && cd /build/php && zip -q -r -y /build/php.zip . \
    && cd /build/imagick && zip -q -r -y /build/imagick.zip . \
    && cd /build/function && zip -q /build/function.zip bootstrap
COPY deploy.sh /usr/local/bin/deploy
ENTRYPOINT ["/usr/local/bin/deploy"]
