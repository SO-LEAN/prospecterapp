# The local stack

What is in force; the reasons are in the records it names (`docs/adr`), read only to know why or before changing it (ADR 0090).

Everything runs in Docker, driven by make (ADR 0015, 0080): MiniStack emulates the AWS services (ADR 0048), the API and the storage run only in their Bref Lambdas on it (ADR 0055), the command line on the PHP of the Lambdas (ADR 0056), nginx serves the hosts. The commands are in the root `CLAUDE.md`, its traps in its Gotchas.

## The API

- The API runs in a Lambda with the Bref layer (FPM runtime, `bref/bref`) behind an HTTP API of MiniStack, the repository mounted in the dev environment (ADR 0053, 0055; `make start`): `php/conf.d/bref.ini` enables the extensions the layer ships disabled (`intl`; a new extension the API needs goes there too), `TRUSTED_PROXIES` (`REMOTE_ADDR` in the function, empty for the tests) lets Symfony trust the `X-Forwarded-Proto` of the gateway. The functions write the cache and the logs of dev in the repository, PHP-FPM serving as its owner (`docker/lambda/bootstrap`); the profiler of dev keeps its profiles in `var/profiler`, out of the cache, so that a `cache:clear` (`make db`) keeps the history: read it on https://api.local.prospecter.io/_profiler, or from the `X-Debug-Token-Link` of a response in the network panel; `make profiler-clear` empties it. The tests run on the command line of the `php` container.
- The relay of the outbox (ADR 0098) is the function `prospecter-dev-relay` (`BREF_RUNTIME=function`, the service `App\Shared\Messaging\OutboxRelay`), called by a mapping on the stream of each table, publishing on the bus `prospecter-dev-integration` of MiniStack; `docker/lambda/deploy.sh` deploys it with the API, and `make db`, recreating the tables and so their streams, again (`deploy.sh relay`). MiniStack neither splits a failing batch nor stops retrying it: a record is kept a minute at most (`--maximum-record-age-in-seconds 60`), then its batch goes to the queue `prospecter-dev-relay-failures`. The delivery stays `sync` (`apps/api/.env`); with `INTEGRATION_EVENTS_DELIVERY=outbox` in `apps/api/.env.local`, a command writes its item `OUTBOX#`, and the relay logs each event published with its `eventId` and its correlation in the logs of dev (`apps/api/var/log/dev.log`). The local bootstrap starts PHP from the root of the code, as AWS does, and, for a function of the runtime `function`, as the owner of the repository.

## The storage

- Both entries reach the app in its Lambda `prospecter-dev-storage` (ADR 0054, 0055: the mounted repository, the layers of PHP and imagick of Bref, `BREF_BINARY_RESPONSES` for the images), through `docker/nginx/storage-lambda.conf`.

## The doubles of the journeys

- nginx serves on the Docker network only the double of the geocoder of the IGN (`http://nginx:8081/search`, `conf.d/geocoder-double.conf`); `make e2e` points the API at it while the journeys run (`docker/lambda/deploy.sh geocoder <url>`), then at the service of `apps/api/.env` again (ADR 0092, `docs/architecture/tests.md`).
