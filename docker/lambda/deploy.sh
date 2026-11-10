#!/bin/sh
# Deploys the API and the storage to MiniStack (ADR 0053, 0054): the layers of Bref, a function per app running the
# mounted repository in the dev environment, and an HTTP API in front of each, whose id is fixed so that nginx
# knows where to send the hosts. Run again any time: the functions are recreated, the rest kept.
# deploy geocoder <url>: only the geocoder the API asks, its double while the journeys run (make e2e), the service
# of the IGN of apps/api/.env with no url.
# deploy relay: only the bus, the relay of the outbox and the mappings of the streams of the tables, which make db
# recreates.
set -eu

aws() { command aws --endpoint-url "$AWS_ENDPOINT_URL" --output text "$@"; }

# MiniStack may have just been started or recreated (make start)
for second in $(seq 1 30); do
    curl -sf -o /dev/null "$AWS_ENDPOINT_URL/_ministack/health" && break
    sleep 1
done

role=$(aws iam get-role --role-name prospecter-dev-lambda --query Role.Arn 2>/dev/null) || role=$(aws iam create-role --role-name prospecter-dev-lambda \
    --assume-role-policy-document '{"Version":"2012-10-17","Statement":[{"Effect":"Allow","Principal":{"Service":"lambda.amazonaws.com"},"Action":"sts:AssumeRole"}]}' \
    --query Role.Arn)

# the ARN of a layer, published once per version
layer() {
    arn=$(aws lambda list-layer-versions --layer-name "$1" --query 'LayerVersions[0].LayerVersionArn')
    if [ "$arn" = None ] || [ -z "$arn" ]; then
        arn=$(aws lambda publish-layer-version --layer-name "$1" --compatible-runtimes provided.al2023 \
            --zip-file "fileb://$2" --query LayerVersionArn)
    fi
    echo "$arn"
}

# environment <app> <variables>: the variables of the function of an app, those of Bref and its own
environment() {
    echo "Variables={BREF_RUNTIME=fpm,BREF_LOCAL_HANDLER=public/index.php,BREF_LOCAL_ROOT=/var/www/src/apps/$1$2}"
}

# the API trusts the gateway in front of it; the geocoder of its .env unless one is given
API_VARIABLES=,TRUSTED_PROXIES=REMOTE_ADDR
api_variables() {
    echo "$API_VARIABLES${1:+,APP_GEOCODER_URL=$1}"
}

# lambda_function <app> <variables> <layers...>: the function of an app, its code the mounted apps/<app>
lambda_function() {
    app=$1 variables=$2
    shift 2
    aws lambda delete-function --function-name "prospecter-dev-$app" >/dev/null 2>&1 || true
    aws lambda create-function --function-name "prospecter-dev-$app" --runtime provided.al2023 --handler public/index.php \
        --role "$role" --zip-file fileb:///build/function.zip --layers "$@" --memory-size 1024 --timeout 28 \
        --environment "$(environment "$app" "$variables")" --query FunctionArn
}

# api_geocoder [url]: the geocoder the API asks, a double that must answer first; MiniStack recycles the containers
# of a function whose environment changes, the next request starting a new one
api_geocoder() {
    if [ -n "${1:-}" ] && ! curl -sf -o /dev/null "$1"; then
        echo "No geocoder answers at $1: make start builds nginx with its double" >&2
        exit 1
    fi
    aws lambda update-function-configuration --function-name prospecter-dev-api \
        --environment "$(environment api "$(api_variables "${1:-}")")" >/dev/null
    aws lambda wait function-updated --function-name prospecter-dev-api
    echo "The API asks the geocoder ${1:-of apps/api/.env}"
}

# http_api <app> <function arn> [cors]: the HTTP API of an app, stage local
http_api() {
    name=prospecter-dev-$1
    if ! aws apigatewayv2 get-api --api-id "$name" >/dev/null 2>&1; then
        aws apigatewayv2 create-api --name "$name" --protocol-type HTTP --tags ms-custom-id="$name" >/dev/null
    fi
    if [ -n "${3:-}" ]; then
        aws apigatewayv2 update-api --api-id "$name" --cors-configuration "$3" >/dev/null
    fi
    for integration in $(aws apigatewayv2 get-integrations --api-id "$name" --query 'Items[].IntegrationId'); do
        aws apigatewayv2 delete-integration --api-id "$name" --integration-id "$integration"
    done
    for route in $(aws apigatewayv2 get-routes --api-id "$name" --query 'Items[].RouteId'); do
        aws apigatewayv2 delete-route --api-id "$name" --route-id "$route"
    done
    integration=$(aws apigatewayv2 create-integration --api-id "$name" --integration-type AWS_PROXY \
        --integration-uri "$2" --payload-format-version 2.0 --query IntegrationId)
    aws apigatewayv2 create-route --api-id "$name" --route-key '$default' --target "integrations/$integration" >/dev/null
    aws apigatewayv2 get-stage --api-id "$name" --stage-name local >/dev/null 2>&1 \
        || aws apigatewayv2 create-stage --api-id "$name" --stage-name local --auto-deploy >/dev/null
}

# deploy_relay: the bus, and the relay of the outbox (ADR 0098), a function of the API reading the stream of each
# table; MiniStack neither splits a failing batch nor stops retrying it (spike of MiniStack 1.5.27): the age of a
# record is limited to a minute, after which the batch goes to the queue of the failures, or it would block the table
deploy_relay() {
    php=$(layer "$PHP_LAYER" /build/php.zip)
    aws events describe-event-bus --name prospecter-dev-integration >/dev/null 2>&1 \
        || aws events create-event-bus --name prospecter-dev-integration >/dev/null
    failures=$(aws sqs create-queue --queue-name prospecter-dev-relay-failures --attributes MessageRetentionPeriod=1209600 --query QueueUrl)
    failures=$(aws sqs get-queue-attributes --queue-url "$failures" --attribute-names QueueArn --query Attributes.QueueArn)

    aws lambda delete-function --function-name prospecter-dev-relay >/dev/null 2>&1 || true
    relay=$(aws lambda create-function --function-name prospecter-dev-relay --runtime provided.al2023 --handler bootstrap \
        --role "$role" --zip-file fileb:///build/function.zip --layers "$php" --memory-size 512 --timeout 28 \
        --environment 'Variables={BREF_RUNTIME=function,BREF_LOCAL_HANDLER=App\Shared\Messaging\OutboxRelay,BREF_LOCAL_ROOT=/var/www/src/apps/api}' \
        --query FunctionArn)

    for mapping in $(aws lambda list-event-source-mappings --function-name prospecter-dev-relay --query 'EventSourceMappings[].UUID'); do
        aws lambda delete-event-source-mapping --uuid "$mapping" >/dev/null
    done
    for context in accounts personal-data prospecting; do
        stream=$(aws dynamodb describe-table --table-name "prospecter-dev-$context" --query Table.LatestStreamArn 2>/dev/null) || stream=None
        if [ "$stream" = None ]; then
            echo "No stream of the table prospecter-dev-$context: make db creates the tables, then relays them" >&2
            continue
        fi
        aws lambda create-event-source-mapping --function-name "$relay" --event-source-arn "$stream" \
            --starting-position TRIM_HORIZON --batch-size 10 --bisect-batch-on-function-error --maximum-retry-attempts 5 \
            --maximum-record-age-in-seconds 60 --destination-config "OnFailure={Destination=$failures}" \
            --filter-criteria '{"Filters":[{"Pattern":"{\"eventName\":[\"INSERT\"],\"dynamodb\":{\"Keys\":{\"PK\":{\"S\":[{\"prefix\":\"OUTBOX#\"}]}}}}"}]}' >/dev/null
    done

    echo "Lambda prospecter-dev-relay relaying the outbox of the tables to the bus prospecter-dev-integration"
}

# deploy_apps: the functions of the API and the storage behind their HTTP APIs, warmed
deploy_apps() {
    php=$(layer "$PHP_LAYER" /build/php.zip)
    imagick=$(layer "$IMAGICK_LAYER" /build/imagick.zip)

    # MiniStack answers every preflight from the CORS of the HTTP API, never passing it to the function as AWS does
    # without one: the API gets the same CORS as its nelmio_cors in dev
    http_api api "$(lambda_function api "$(api_variables)" "$php")" \
        'AllowOrigins=https://local.prospecter.io,AllowMethods=GET,OPTIONS,POST,PATCH,DELETE,AllowHeaders=content-type,authorization,ExposeHeaders=location,x-debug-token,x-debug-token-link,MaxAge=3600'
    # the storage answers images: Bref sends them base64-encoded only when asked
    http_api storage "$(lambda_function storage ,BREF_BINARY_RESPONSES=1 "$php" "$imagick")"

    # warm a few functions and the caches of Symfony: the SPA asks several resources at once, each in a function
    for warm in 1 2 3; do
        curl -s -o /dev/null -H "Host: $API_HOST" "$AWS_ENDPOINT_URL/_aws/execute-api/prospecter-dev-api/local/api/user-pool" &
    done
    curl -s -o /dev/null "$AWS_ENDPOINT_URL/_aws/execute-api/prospecter-dev-storage/local/storage/small/none" &
    wait

    echo "Lambdas prospecter-dev-api and prospecter-dev-storage with $php and $imagick, behind their HTTP APIs (stage local)"
    deploy_relay
}

if [ "${1:-}" = geocoder ]; then
    api_geocoder "${2:-}"
elif [ "${1:-}" = relay ]; then
    deploy_relay
else
    deploy_apps
fi
