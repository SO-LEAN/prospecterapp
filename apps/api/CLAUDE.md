# api (Symfony 8.1)

## Role

A JSON API that exposes the use cases of `packages/core`. It is a resource server for the SPA (`apps/front`). Members sign in with a Cognito user pool. The API checks the pool's tokens and adapts its credentials (ADR 0052). There are no server-rendered pages. The full contract is `openapi.json`, which is generated: do not edit it. Swagger UI shows it at `https://api.prospecter.localhost/api/doc`.

## Project rules

- No Doctrine, no migrations: the aggregates live in DynamoDB (ADR 0048). `bin/console app:tables:recreate` creates the tables (`make db` runs it). The tests create them once per process, and each test then gets the demonstration data back (ADR 0180). `bin/console app:user-pool:recreate` creates the user pool.
- No Symfony CLI: run commands through `docker compose run --rm php ...` (see `docs/architecture/commands.md`).
- No API Platform: plain controllers documented with NelmioApiDocBundle attributes.
- Services in YAML: the hexagon's ports and services are registered in `config/services.yaml`, because the core is pure PHP without Symfony attributes.
- Messenger is synchronous: the command and event buses have no transport and are handled within the request (ADR 0029, 0042). Reactions run after the command, in the worker, through the outbox and the EventBridge bus (ADR 0101, `docs/architecture/contexts.md`).
- No test reads the real clock (ADR 0150). The controllers read the fixed `clock` of `when@test`. A test names its moment. The tables forget from `app.commit_clock`, which is fixed in 2100 (`docs/architecture/tests.md`).
- Every PHP file declares `declare(strict_types=1)`, `bin/console` included. `make lint` refuses a file without it, `config/bundles.php` included. `make fix` puts the declaration back after Flex removes it. Symfony writes `config/reference.php` at each build of the debug container. That file is not committed (ADR 0194).
- PHPStan checks the API at level `max`, ignoring only `missingType.iterableValue` (ADR 0192). `phpstan-baseline.neon` holds the errors of levels 7 to 10 that the API had when it moved to `max` (backlog #259). New code adds none to it. An entry ignores a message in a file up to a count, so it can hide a new error with the same message. A change to a file in the baseline fixes the errors on the lines it touches. Then it regenerates the baseline smaller (`docker compose run --rm --no-deps php vendor/bin/phpstan analyse --memory-limit=1G --generate-baseline=phpstan-baseline.neon`).
- `Request::get()` no longer exists: use `$request->attributes`, `$request->query` or `$request->request`.
- Symfony 8.1 is newer than what you remember: look things up rather than guess. Use `make console CMD="debug:container <id>"` (or `debug:router`, `debug:config <bundle>`, `config:dump-reference <bundle>`) and the sources under `vendor/`.

## What is in force

Read before working on the API: `docs/architecture/api.md` (endpoints, inputs, resources, presenters, links, errors, access, wiring), `docs/architecture/persistence.md` (DynamoDB: ports and adapters, tables, items, documents, search), `docs/architecture/auth.md` (Cognito, tokens, the firewall), `docs/architecture/contexts.md` (the anticorruption layers), `docs/architecture/local-stack.md` (the API in its Lambda), `docs/architecture/infra.md` (on AWS), `docs/architecture/tests.md` (the API tests).
