#!/bin/sh
# The local authority of the machine and the certificate of the local hosts it signs (make ca): created once, kept when
# run again, the certificate signed again only when it is missing or no longer from that authority. The authority may
# sign for local.prospecter.io and its subdomains only (a critical name constraint): imported once in a browser, it
# vouches for no other site. Run as root, the directory possibly created by Docker; its files given to OWNER, the keys
# readable by OWNER alone.
set -eu

DIR=/etc/ssl/local
CA_CRT=$DIR/ca.pem
CA_KEY=$DIR/ca-key.pem
SIGNED_DIR=$DIR/signed
CRT=$SIGNED_DIR/project.crt
KEY=$SIGNED_DIR/project.key
DOMAIN=local.prospecter.io
CA_DAYS=7305
CERTIFICATE_DAYS=3653

give_to_owner() {
    chown "$OWNER" "$@"
}

keep_private() {
    chmod 0600 "$1"
    give_to_owner "$1"
}

create_authority() {
    config=$(mktemp)
    cat >"$config" <<EOF
[req]
distinguished_name = subject
x509_extensions = authority
prompt = no
[subject]
O = Prospecter
CN = Prospecter local authority
[authority]
basicConstraints = critical, CA:TRUE, pathlen:0
keyUsage = critical, keyCertSign, cRLSign
subjectKeyIdentifier = hash
nameConstraints = critical, permitted;DNS:$DOMAIN, permitted;DNS:.$DOMAIN
EOF
    openssl req -x509 -config "$config" -newkey ec -pkeyopt ec_paramgen_curve:P-256 -noenc -sha256 \
        -days "$CA_DAYS" -keyout "$CA_KEY" -out "$CA_CRT" 2>/dev/null
    rm "$config"
    keep_private "$CA_KEY"
    give_to_owner "$CA_CRT"
}

signed_by_authority() {
    [ -f "$CRT" ] && [ -f "$KEY" ] && openssl verify -CAfile "$CA_CRT" "$CRT" >/dev/null 2>&1
}

sign_certificate() {
    mkdir -p "$SIGNED_DIR"
    extensions=$(mktemp)
    cat >"$extensions" <<EOF
basicConstraints = critical, CA:FALSE
keyUsage = critical, digitalSignature
extendedKeyUsage = serverAuth
subjectKeyIdentifier = hash
authorityKeyIdentifier = keyid
subjectAltName = DNS:$DOMAIN, DNS:api.$DOMAIN, DNS:static.$DOMAIN
EOF
    request=$(mktemp)
    openssl req -new -newkey ec -pkeyopt ec_paramgen_curve:P-256 -noenc -subj "/O=Prospecter/CN=$DOMAIN" \
        -keyout "$KEY" -out "$request" 2>/dev/null
    openssl x509 -req -in "$request" -CA "$CA_CRT" -CAkey "$CA_KEY" -set_serial "0x$(openssl rand -hex 16)" \
        -sha256 -days "$CERTIFICATE_DAYS" -extfile "$extensions" -out "$CRT" 2>/dev/null
    rm "$request" "$extensions"
    keep_private "$KEY"
    give_to_owner "$CRT" "$SIGNED_DIR"
}

if [ -f "$CA_CRT" ] && [ -f "$CA_KEY" ]; then
    echo "local authority kept: ${SHOWN_DIR:-$DIR}/ca.pem"
else
    create_authority
    echo "local authority created: ${SHOWN_DIR:-$DIR}/ca.pem, to import once in the browser (README)"
fi

if signed_by_authority; then
    echo "certificate of $DOMAIN kept"
else
    sign_certificate
    echo "certificate of $DOMAIN signed: restart the stacks (make stop, make start) to serve it"
fi
