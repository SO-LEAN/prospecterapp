# prospecterapp

Prospecting application built with clean architecture: a React single page application, a Symfony API that delivers the business use cases, an authorization server, a storage for the files, and the use cases library itself.

| Path | Content |
|---|---|
| `apps/front/` | React 19 SPA: TypeScript, Vite, TanStack Router and Query, shadcn/ui + Tailwind CSS, MapLibre |
| `apps/api/` | Symfony 8.1 JSON API, an OAuth 2 resource server documented with NelmioApiDocBundle, DynamoDB (one table per bounded context, MiniStack locally) |
| `apps/auth/` | Symfony 8.1 authorization server: sign-in, OAuth 2 tokens (authorization code + PKCE), login email and password |
| `apps/storage/` | Symfony 8.1 app keeping the uploaded files and serving the images in named sizes |
| `packages/core/` | `prospecter/core`: the bounded contexts Accounts, PersonalData and Prospecting, pure PHP, described by Gherkin scenarios |
| `docker/` | PHP 8.5, Node 24 and nginx images |

Everything runs in Docker: only Docker (with the compose plugin) and make are needed on the host.

Documentation: [architecture decisions](docs/adr/README.md), [clean code](docs/clean-code.md), [core tests](docs/core-tests.md), [front architecture](docs/front-architecture.md), [design system](docs/design-system.md), [authentication](docs/auth.md), [monorepo](docs/monorepo.md), [2026 upgrade notes](docs/upgrade-2026.md). Instructions for AI coding assistants are in the `CLAUDE.md` files.

## Setup

Add to `/etc/hosts`:

    127.0.0.1 local.prospecter.io api.local.prospecter.io static.local.prospecter.io

Then:

    make install
    make start

- Application: https://local.prospecter.io, sign in with `prospector@solean-it.io` / `password` (or `assistant@solean-it.io`, who sees the contacts masked)
- API documentation: https://api.local.prospecter.io/api/doc

The certificate is self-signed: accept the browser warning once for each host (`local.`, `api.local.` and `static.local.prospecter.io`).

## Develop

`make` (or `make help`) lists every command.

| Command | Purpose |
|---|---|
| `make start` / `make stop` / `make logs` | Run the stack (the front reloads on save) |
| `make test` | core business rules (Behat) and unit tests, API, auth, storage and front tests |
| `make test-core` / `make test-api` / `make test-auth` / `make test-storage` / `make test-front` | One of them |
| `make e2e` | Playwright journeys against the running stack |
| `make lint` / `make fix` | PHPStan, php-cs-fixer, Rector, Biome, TypeScript |
| `make api-doc` | Regenerate the OpenAPI contracts (API, auth) and the front API types after an endpoint change |
| `make db` | Reset the tables of the API and the database of the auth, with the data of the demonstration |
| `make commands` | List the commands of the core and their handlers |
| `make exec-api` / `make exec-front` | Shell in the PHP or Node container |

Local overrides go in `apps/<app>/.env.local`. Addresses are geocoded by the French State service (IGN Géoplateforme): free, no key, addresses in France only.
