# prospecterapp

Monorepo of a prospecting application built with clean architecture:

| Path | What | Instructions |
|---|---|---|
| `apps/front/` | React 19 SPA (TypeScript, Vite, TanStack Router/Query, shadcn/ui) | `apps/front/CLAUDE.md` |
| `apps/api/` | Symfony 8.1 JSON API documented with NelmioApiDocBundle, a resource server of the Cognito user pool the members sign in with (MiniStack locally, `docs/auth.md`) | `apps/api/CLAUDE.md` |
| `apps/storage/` | Symfony 8.1 app keeping the uploaded files in an S3 bucket and serving them in named sizes (`static.local.prospecter.io`) | `apps/storage/CLAUDE.md` |
| `packages/core/` | `prospecter/core`: the bounded contexts Accounts, PersonalData and Prospecting and their shared kernel (pure PHP), tested by Behat scenarios | `packages/core/CLAUDE.md` |

Docs: `docs/clean-code.md`, `docs/front-architecture.md`, `docs/design-system.md`, `docs/auth.md`, `docs/core-tests.md`, `docs/monorepo.md`, `docs/upgrade-2026.md`.

## Architecture decisions

The index below is loaded in every session; open a record before working on its subject. A decision that changes the structure, a protocol or a tool gets a new record: follow `docs/adr/TEMPLATE.md` (an accepted record is never edited, a new one supersedes it; alternatives only if really weighed).

@docs/adr/README.md

## Commands

Everything runs in Docker (PHP 8.5, Node 24, nginx); nothing but Docker and make on the host. From the repository root (`make` alone lists the commands; a new target gets a `## description` on its line):

- `make install`: images, Composer and pnpm dependencies, and in MiniStack the DynamoDB tables and the Cognito user pool of the API and the S3 bucket of the storage, with the data and the members of the demonstration
- `make start` / `make stop`: https://local.prospecter.io (SPA, sign in `prospector@solean-it.io` / `password`, or `assistant@solean-it.io` / `password`, a prospector without the right to manage personal data), https://api.local.prospecter.io/api/doc (API doc)
- `make test` (core scenarios and unit tests, API, storage, front), `make test-core` (the business rules only), `make e2e` (Playwright on the running stack), `make lint`, `make fix`
- `make api-doc`: after changing an endpoint, regenerate `apps/api/openapi.json` and the front API types (`make test` fails while the committed contract differs from the code)
- `make lambda`: the API in a Lambda with the Bref layer on MiniStack, behind an HTTP API, the repository mounted (no package, dev environment; MiniStack gets the Docker socket in this mode only, ADR 0053); `make fpm` (or `make start`) goes back to PHP-FPM
- `make db`: recreate in MiniStack the dev tables and the user pool of the API and the bucket of the storage (destroys local data); `bin/console app:search:rebuild` rebuilds the search of the API

Other commands: `docker compose run --rm --no-deps php <cmd>` (working dir `apps/api`; add `-w /var/www/src/apps/storage` or `-w /var/www/src/packages/core` for the others) or `docker compose run --rm --no-deps node <cmd>` (working dir `apps/front`).

Before declaring work done: `make test` and `make lint` pass; `make e2e` for changes to a main journey.

## Code conventions

- Clean code as taught by Robert C. Martin: read `docs/clean-code.md` before writing or reviewing code, and leave the code cleaner than you found it. In short: native types everywhere; no comment that repeats a type or explains the code: give the intention a name (extract a function, a constant); short methods with one level of abstraction; command-query separation; docblocks only for what the language cannot express (generics, array shapes); dependencies are injected, never built (ADR 0044, a PHPStan rule of `tools/phpstan`). PHP: readonly DTOs, constructor promotion. TypeScript: strict, no `any`. The guide's **Known debt** lists are the backlog of the improvement iterations.
- Do not trust existing docblocks blindly: some were wrong (a `@var stdClass` led Rector to produce a broken type).
- Business rules belong to `packages/core`, described by its Gherkin scenarios (`docs/core-tests.md`); the API only adapts (HTTP, DynamoDB, storage, authentication); the front only presents.
- One invokable controller per API endpoint, grouped by bounded context (`src/<Context>/Controller`); resource-oriented URLs.

## Git

- Single branch `main`, linear history: work on a short-lived branch, then fast-forward `main`. No merge commits.
- Conventional commits (`feat:`, `fix:`, `refactor:`, `chore:`, `docs:`) explaining why.
- Commit or push only when asked.

## Gotchas

- MiniStack (`ministack` service, ADR 0048) emulates the AWS services locally: DynamoDB and Cognito for the API, S3 for the storage (ADR 0051, 0052), their state in the volume `ministack-data`, reachable from the host (IDE, AWS CLI) at `http://localhost:4566`, region `eu-west-3`, keys `test`/`test`. After `docker compose up -d` recreated `php` or `node`, restart `nginx` (it keeps their former addresses on the SPA and static hosts: 502; the API host resolves them on each request).
- Cognito (ADR 0052): MiniStack makes the ids of the user pool and of its client at random, the API finds them by name and caches them; after the volume `ministack-data` was removed, run `make db`. The SPA reaches the Cognito of MiniStack through `https://api.local.prospecter.io/cognito/` (nginx passes its calls only).

- The Lambda mode (`make lambda`, ADR 0053): MiniStack starts a provided runtime with the handler `bootstrap` and answers the CORS preflights of an HTTP API itself; `docker/lambda/bootstrap` (local only, never in a package for AWS) and the CORS of the HTTP API in `docker/lambda/deploy.sh` make up for it (the same CORS as `nelmio_cors`: change both). The function runs as root on the mounted repository, its `umask 0000` keeping the shared cache writable by `php`. OPcache revalidates the files every 2 s.
- Hosts: `/etc/hosts` needs `local.prospecter.io`, `api.local.prospecter.io` and `static.local.prospecter.io` on 127.0.0.1. The self-signed certificate (generated by nginx in `docker/data/ssl`) must be accepted once per host in the browser; Chrome reports requests to a non-accepted host as failed (503 in the network panel).
- The core library is installed by a Composer `path` repository: `apps/api/vendor/prospecter/core` is a symlink to `packages/core`; production builds copy it with `COMPOSER_MIRROR_PATH_REPOS=1` (`docs/monorepo.md`). Never set `"symlink": true` on that repository.
- Flex recipes and shadcn's generator edit files you did not ask for (a PostgreSQL service in `docker-compose.yml`, broken imports): review `git status` after installing anything.
- Files created in containers belong to the host user (UID/GID exported by the Makefile). Root-owned leftovers: `docker run --rm -v $PWD:/app alpine rm -rf /app/<path>`.
