# 0055. Locally, the API and the storage run only in their Bref Lambdas

- Date: 2026-10-16
- Status: Accepted
- Supersedes: the PHP-FPM mode kept beside the Lambdas, on demand, by [0053](0053-the-api-in-a-local-lambda-with-the-bref-layer.md) and [0054](0054-the-storage-in-a-local-lambda-with-the-imagick-layer.md)
- Implemented by: `docker-compose.yml` (MiniStack with the Docker socket and the `LAMBDA_*` settings, `lambda-deploy`, `php` in the profile `cli`), `make start` / `make deploy`, `docker/nginx/templates/{api,static}.conf.template`, `docker/nginx/storage-lambda.conf`, `docker/php/Dockerfile` (CLI image)

## Context

0053 and 0054 ran the API and the storage in Lambdas with the layers of Bref on demand (`make lambda`), PHP-FPM staying the default, so that MiniStack got the socket of Docker, and with it the rights of root on the host, only when asked. Once the Lambda mode passed the journeys, the user found the PHP-FPM mode useless, and accepted locally that MiniStack keeps the socket.

## Decision

- Locally the API and the storage run only in their Lambdas behind their HTTP APIs: `make start` starts the stack and deploys them (`make deploy` alone redeploys). MiniStack always gets the socket of Docker and runs the functions with the repository mounted.
- nginx sends the API host and both entries of the storage to the HTTP APIs, no longer to PHP-FPM; it no longer mounts the repository.
- The `php` container is the command line only (tests, Composer, console, PHPStan), on the CLI image of PHP, and is not part of the running stack (profile `cli`): `docker compose run --rm php ...` as before.

## Consequences

- One local runtime, the one of production; `make e2e` always runs through the Lambdas.
- MiniStack has the rights of root on the host whenever the stack runs.
- What MiniStack does differently from AWS (0053) is met on every start: the local `bootstrap`, the CORS of the HTTP API kept with `nelmio_cors`.

## Alternatives considered

- Keeping PHP-FPM as the default and the Lambdas on demand (0053, 0054): the user found it useless once the Lambdas worked.
