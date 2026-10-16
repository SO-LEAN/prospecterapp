#!/bin/sh
# Deploys the API to MiniStack (ADR 0053): the Bref layer, a function running the mounted repository in the dev
# environment, and an HTTP API in front of it whose id is fixed, so that nginx knows where to send the API host.
# Run again any time: the function is recreated, the rest kept.
set -eu

name=prospecter-dev-api
aws() { command aws --endpoint-url "$AWS_ENDPOINT_URL" --output text "$@"; }

# MiniStack may have just been recreated (make lambda switches its configuration)
for second in $(seq 1 30); do
    curl -sf -o /dev/null "$AWS_ENDPOINT_URL/_ministack/health" && break
    sleep 1
done

role=$(aws iam get-role --role-name "$name" --query Role.Arn 2>/dev/null) || role=$(aws iam create-role --role-name "$name" \
    --assume-role-policy-document '{"Version":"2012-10-17","Statement":[{"Effect":"Allow","Principal":{"Service":"lambda.amazonaws.com"},"Action":"sts:AssumeRole"}]}' \
    --query Role.Arn)

# published once per version of Bref (BREF_LAYER, docker/lambda/Dockerfile)
layer=$(aws lambda list-layer-versions --layer-name "$BREF_LAYER" --query 'LayerVersions[0].LayerVersionArn')
if [ "$layer" = None ] || [ -z "$layer" ]; then
    layer=$(aws lambda publish-layer-version --layer-name "$BREF_LAYER" --compatible-runtimes provided.al2023 \
        --zip-file fileb:///build/layer.zip --query LayerVersionArn)
fi

aws lambda delete-function --function-name "$name" >/dev/null 2>&1 || true
function=$(aws lambda create-function --function-name "$name" --runtime provided.al2023 --handler public/index.php \
    --role "$role" --zip-file fileb:///build/function.zip --layers "$layer" --memory-size 1024 --timeout 28 \
    --environment 'Variables={BREF_RUNTIME=fpm,BREF_LOCAL_HANDLER=public/index.php,BREF_LOCAL_ROOT=/var/www/src/apps/api,TRUSTED_PROXIES=REMOTE_ADDR}' \
    --query FunctionArn)

if ! aws apigatewayv2 get-api --api-id "$name" >/dev/null 2>&1; then
    aws apigatewayv2 create-api --name "$name" --protocol-type HTTP --tags ms-custom-id="$name" >/dev/null
fi
# MiniStack answers every preflight from the CORS of the HTTP API, never passing it to the function as AWS does
# without one: the same CORS as nelmio_cors of the API
aws apigatewayv2 update-api --api-id "$name" --cors-configuration \
    'AllowOrigins=https://local.prospecter.io,AllowMethods=GET,OPTIONS,POST,PATCH,DELETE,AllowHeaders=content-type,authorization,ExposeHeaders=location,MaxAge=3600' >/dev/null
for integration in $(aws apigatewayv2 get-integrations --api-id "$name" --query 'Items[].IntegrationId'); do
    aws apigatewayv2 delete-integration --api-id "$name" --integration-id "$integration"
done
for route in $(aws apigatewayv2 get-routes --api-id "$name" --query 'Items[].RouteId'); do
    aws apigatewayv2 delete-route --api-id "$name" --route-id "$route"
done
integration=$(aws apigatewayv2 create-integration --api-id "$name" --integration-type AWS_PROXY \
    --integration-uri "$function" --payload-format-version 2.0 --query IntegrationId)
aws apigatewayv2 create-route --api-id "$name" --route-key '$default' --target "integrations/$integration" >/dev/null
aws apigatewayv2 get-stage --api-id "$name" --stage-name local >/dev/null 2>&1 \
    || aws apigatewayv2 create-stage --api-id "$name" --stage-name local --auto-deploy >/dev/null

# warm a few functions and the cache of Symfony: the SPA asks several resources at once, each in a function
for warm in 1 2 3; do
    curl -s -o /dev/null -H "Host: $API_HOST" "$AWS_ENDPOINT_URL/_aws/execute-api/$name/local/api/user-pool" &
done
wait

echo "Lambda $name with $layer, behind the HTTP API $name (stage local)"
