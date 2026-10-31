# api (Symfony 8.1)

@AGENTS.md

The generic Symfony guidance above is overridden by the project rules below when they differ.

## Role

A JSON API that exposes the use cases of `packages/core`, a resource server for the SPA (`apps/front`): the members sign in with a Cognito user pool, whose tokens the API checks and whose credentials it adapts (ADR 0052). No server-rendered page. Full contract: `openapi.json` (generated, do not edit) and Swagger UI on `https://api.local.prospecter.io/api/doc`.

## Project overrides

- No Doctrine, no migrations: the aggregates live in DynamoDB (ADR 0048), the tables are created by `bin/console app:tables:recreate` (`make db`, the tests recreate them); the user pool by `bin/console app:user-pool:recreate`.
- No Symfony CLI: run commands through `docker compose run --rm php ...` (see the root `CLAUDE.md`).
- No API Platform: plain controllers documented with NelmioApiDocBundle attributes.
- Services in YAML: the ports and the services of the hexagon are registered in `config/services.yaml`, the core being pure PHP without Symfony attributes.
- Messenger is synchronous: the command and event buses have no transport, handled within the request (ADR 0029, 0042).
- `Request::get()` no longer exists: use `$request->attributes`, `$request->query` or `$request->request`.

## What is in force

Read before working on it: `docs/architecture/api.md` (endpoints, inputs, resources, presenters, links, errors, access, wiring), `docs/architecture/persistence.md` (DynamoDB: ports and adapters, tables, items, documents, search), `docs/architecture/auth.md` (Cognito, tokens, the firewall), `docs/architecture/contexts.md` (the anticorruption layers), `docs/architecture/local-stack.md` (the API in its Lambda), `docs/architecture/infra.md` (on AWS), `docs/architecture/tests.md` (the tests of the API).
