# 0053. Locally, the API runs on demand in a Lambda with the Bref layer, the repository mounted

- Date: 2026-10-16
- Status: Accepted, on demand superseded by [0055](0055-locally-the-apps-run-only-in-their-lambdas.md)
- Implemented by: `docker-compose.lambda.yml`, `docker/lambda/{Dockerfile,bootstrap,deploy.sh}`, `docker/nginx/api-runtime/{fpm,lambda}.conf`, `make lambda` / `make fpm`, `apps/api/php/conf.d/bref.ini`, `trusted_proxies` of `apps/api/config/packages/framework.yaml`

## Context

The API is to run on AWS in a Lambda with Bref behind an API Gateway HTTP API (0048). The user wants to run it so locally before any AWS, on MiniStack, with the layer of Bref, in the dev environment with its dependencies and the profiler, and without packaging the code at each change. MiniStack runs a `provided.al2023` function in the official Lambda image through the Docker of the host, copying the layers into `/opt`, but it starts every provided runtime with the handler `bootstrap` instead of the one configured, runs the function as root, and answers every CORS preflight of an HTTP API itself from its configuration.

## Decision

- `make lambda` runs the API in a function `prospecter-dev-api` (`provided.al2023`, `BREF_RUNTIME=fpm`) with the layer of Bref (the `/opt` of `bref/php-85`, published once per version), behind an HTTP API whose id is fixed (`ms-custom-id`); `make fpm`, and `make start`, go back to PHP-FPM. Deployed by a script (`docker/lambda/deploy.sh`, AWS CLI), no Terraform locally: Terraform will be written for AWS.
- The repository is mounted in the function as in the `php` container (`LAMBDA_DOCKER_FLAGS`): no package, a change shows on the next request; the vendor of dev, the link to the core and the cache of the dev environment are those of PHP-FPM.
- The code of the function is only a local `bootstrap`: it gives Bref back the handler, the root of the mounted code and its `php/conf.d`, and writes with a `umask` of `0000`, so that what the function writes as root in the shared cache stays writable by the user of the `php` container. It never goes into a package for AWS.
- The socket of Docker is mounted in MiniStack in this mode only (`docker-compose.lambda.yml`): it gives MiniStack the rights of root on the host.
- nginx sends the API host by path to the HTTP API (`/_aws/execute-api/<id>/local/…`), the host kept for the routes of the API; it tells the API the request was https, the API trusting `X-Forwarded-Proto` where `TRUSTED_PROXIES` names a proxy (in the function only). The HTTP API gets the CORS of `nelmio_cors`, MiniStack answering the preflights from it.

## Consequences

- The cold start of a function takes one to two seconds; `make lambda` warms three of them, kept an hour.
- The tests of the API stay on PHP-FPM; `make e2e` runs in either mode.
- What MiniStack does differently from AWS (the handler, the preflights, IAM not enforced) is only met on AWS: the dev instance on AWS stays the safety net.

## Alternatives considered

- A Terraform shared by MiniStack and AWS: rejected by the user, too much of AWS (IAM above all) not being emulated to make it worth it locally.
- The container image of Bref instead of its layer: the user wants the layer, as in production.
- A package of the code (zip) deployed at each change: the user wants the mounted code of the dev environment.

## Revisit when

- MiniStack starts a provided runtime with its configured handler, or passes the preflights of an HTTP API without CORS to its integration: the local `bootstrap` or the CORS of the HTTP API can go.
