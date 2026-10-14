# prospecterapp

Prospecting application built with clean architecture: a React single page application, a Symfony API that exposes the business use cases, and the use cases library itself.

| Path | Content |
|---|---|
| `apps/front/` | React 19 SPA: TypeScript, Vite, TanStack Router and Query, shadcn/ui + Tailwind CSS, MapLibre |
| `apps/api/` | Symfony 8.1 JSON API documented with NelmioApiDocBundle, OAuth 2 authorization server, Doctrine ORM 3 on SQLite |
| `packages/core/` | `prospecter/core`: entities, use cases and gateway interfaces, pure PHP |
| `docker/` | PHP 8.5, Node 24 and nginx images |

Everything runs in Docker: only Docker (with the compose plugin) and make are needed on the host.

Documentation: [front architecture](docs/front-architecture.md), [design system](docs/design-system.md), [authentication](docs/auth.md), [monorepo](docs/monorepo.md), [2026 upgrade notes](docs/upgrade-2026.md). Instructions for AI coding assistants are in the `CLAUDE.md` files.

## Setup

Add to `/etc/hosts`:

    127.0.0.1 local.prospecter.io api.local.prospecter.io static.local.prospecter.io

Then:

    make install
    make start

- Application: https://local.prospecter.io, sign in with `prospector` / `password`
- API documentation: https://api.local.prospecter.io/api/doc

The certificate is self-signed: accept the browser warning once for each host (`local.`, `api.local.` and `static.local.prospecter.io`).

## Develop

| Command | Purpose |
|---|---|
| `make start` / `make stop` / `make logs` | Run the stack (the front reloads on save) |
| `make test` | API, core and front unit tests |
| `make e2e` | Playwright journeys against the running stack |
| `make lint` / `make fix` | PHPStan, php-cs-fixer, Rector, Biome, TypeScript |
| `make api-doc` | Regenerate the OpenAPI contract and the front API types after an API change |
| `make db` | Reset the database and reload the fixtures |
| `make exec-api` / `make exec-front` | Shell in the PHP or Node container |
| `make usecases` | List the registered use cases |

Local overrides go in `apps/api/.env.local` (e.g. a real `APP_GOOGLE_API_KEY` to geocode addresses) and `apps/front/.env.local`.
