# prospecterapp

Monorepo of the Prospecter application and of its clean architecture use cases library.

| Directory                   | Content                                                                 |
|-----------------------------|-------------------------------------------------------------------------|
| `apps/api/`                   | Symfony 8.1 application (Doctrine ORM 3 on SQLite, AssetMapper + Sass)  |
| `packages/core/` | `prospecter/core` library: entities, use cases, gateways        |
| `docker/`                   | PHP 8.5 and nginx images                                                |

Everything runs in Docker: no PHP or Node needed on the host.

Further reading: [monorepo](docs/monorepo.md), [2026 upgrade notes](docs/upgrade-2026.md). Instructions for AI coding assistants are in the `CLAUDE.md` files.

## Requirements

- Docker with the compose plugin (`docker compose`)
- make

Add to your `/etc/hosts`:

    127.0.0.1 local.prospecter.io
    127.0.0.1 static.local.prospecter.io

## Install

    make install

Builds the images, installs Composer dependencies, creates the database with fixtures and compiles the assets.

## Run

    make start

Open https://local.prospecter.io and log in with `prospector` / `password`.

The certificate is self-signed: accept the browser warning once for https://local.prospecter.io **and** https://static.local.prospecter.io (assets are served from the static host).

    make stop

## Develop

| Command         | Purpose                                        |
|-----------------|------------------------------------------------|
| `make watch`    | Recompile Sass on change                       |
| `make db`       | Reset the database and reload the fixtures     |
| `make test`     | Run the test suite                             |
| `make stan`     | Static analysis (PHPStan)                      |
| `make cs`       | Check coding standards (`make cs-fix` to fix)  |
| `make exec`     | Shell in the PHP container                     |
| `make usecases` | List the registered use cases                  |

Local configuration overrides go in `apps/api/.env.local` (not committed), e.g. a real `APP_GOOGLE_API_KEY` for maps and geolocation.

## The core library

The API requires `prospecter/core` through a Composer `path` repository: `apps/api/vendor/prospecter/core` is a symlink to `packages/core`, so changes to the library are picked up immediately. Its tests run with the API suite (`make test`). The library is internal to this repository and is not published.
