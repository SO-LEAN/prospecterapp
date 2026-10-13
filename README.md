# prospecterapp

Prospecting application built on the [cleanprospecter](https://github.com/SO-LEAN/cleanprospecter) use cases library.

PHP 8.5, Symfony 8.1, Doctrine ORM 3 (SQLite), AssetMapper with Sass. Everything runs in Docker: no PHP or Node needed on the host.

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

Local configuration overrides go in `.env.local` (not committed), e.g. a real `APP_GOOGLE_API_KEY` for maps and geolocation.
