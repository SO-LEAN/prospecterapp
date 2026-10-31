# The local stack

What is in force; the reasons are in the records it names (`docs/adr`), read only to know why or before changing it (ADR 0090).

Everything runs in Docker, driven by make (ADR 0015, 0080): MiniStack emulates the AWS services (ADR 0048), the API and the storage run only in their Bref Lambdas on it (ADR 0055), the command line on the PHP of the Lambdas (ADR 0056), nginx serves the hosts. The commands are in the root `CLAUDE.md`, its traps in its Gotchas.

## The API

- The API runs in a Lambda with the Bref layer (FPM runtime, `bref/bref`) behind an HTTP API of MiniStack, the repository mounted in the dev environment (ADR 0053, 0055; `make start`): `php/conf.d/bref.ini` enables the extensions the layer ships disabled (`intl`; a new extension the API needs goes there too), `TRUSTED_PROXIES` (`REMOTE_ADDR` in the function, empty for the tests) lets Symfony trust the `X-Forwarded-Proto` of the gateway. The functions write the cache and the logs of dev in the repository, PHP-FPM serving as its owner (`docker/lambda/bootstrap`); the profiler of dev keeps its profiles in `var/profiler`, out of the cache, so that a `cache:clear` (`make db`) keeps the history: read it on https://api.local.prospecter.io/_profiler, or from the `X-Debug-Token-Link` of a response in the network panel; `make profiler-clear` empties it. The tests run on the command line of the `php` container.

## The storage

- Both entries reach the app in its Lambda `prospecter-dev-storage` (ADR 0054, 0055: the mounted repository, the layers of PHP and imagick of Bref, `BREF_BINARY_RESPONSES` for the images), through `docker/nginx/storage-lambda.conf`.
