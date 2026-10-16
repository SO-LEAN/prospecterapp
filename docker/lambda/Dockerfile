# Deploys the API to MiniStack as a Lambda with the Bref layer (ADR 0053): the layer is the /opt of the Bref
# image, the code of the function only the local bootstrap, the repository being mounted in the function.
FROM bref/php-85:3.0.9 AS bref

FROM amazon/aws-cli:2.37.2
# the layer of the Bref image above, named after its version
ENV BREF_LAYER=bref-php-85-3-0-9
RUN yum install -y -q zip && yum clean all
COPY --from=bref /opt /build/layer
COPY bootstrap /build/function/bootstrap
RUN chmod +x /build/function/bootstrap \
    && cd /build/layer && zip -q -r -y /build/layer.zip . \
    && cd /build/function && zip -q /build/function.zip bootstrap
COPY deploy.sh /usr/local/bin/deploy
ENTRYPOINT ["/usr/local/bin/deploy"]
