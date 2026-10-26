#!/bin/sh
# Deploys the API and the storage to MiniStack (ADR 0053, 0054): the layers of Bref, a function per app running the
# mounted repository in the dev environment, and an HTTP API in front of each, whose id is fixed so that nginx
# knows where to send the hosts. Run again any time: the functions are recreated, the rest kept.
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

# lambda_function <app> <variables> <layers...>: the function of an app, its code the mounted apps/<app>
lambda_function() {
    app=$1 variables=$2
    shift 2
    aws lambda delete-function --function-name "prospecter-dev-$app" >/dev/null 2>&1 || true
    aws lambda create-function --function-name "prospecter-dev-$app" --runtime provided.al2023 --handler public/index.php \
        --role "$role" --zip-file fileb:///build/function.zip --layers "$@" --memory-size 1024 --timeout 28 \
        --environment "Variables={BREF_RUNTIME=fpm,BREF_LOCAL_HANDLER=public/index.php,BREF_LOCAL_ROOT=/var/www/src/apps/$app$variables}" \
        --query FunctionArn
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

php=$(layer "$PHP_LAYER" /build/php.zip)
imagick=$(layer "$IMAGICK_LAYER" /build/imagick.zip)

# MiniStack answers every preflight from the CORS of the HTTP API, never passing it to the function as AWS does
# without one: the API gets the same CORS as its nelmio_cors in dev
http_api api "$(lambda_function api ,TRUSTED_PROXIES=REMOTE_ADDR "$php")" \
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
