# prospecterapp

Monorepo: a Symfony app (`webapp/`) built on a clean architecture library (`packages/cleanprospecter/`, Composer package `so-lean/cleanprospecter`, internal, not published). More context in `webapp/CLAUDE.md`, `packages/cleanprospecter/CLAUDE.md` and `docs/`.

## Commands

Everything runs in Docker; there is no PHP, Composer or Node on the host. Use the Makefile from the repository root:

- `make install` builds images, installs dependencies, creates the database, compiles assets
- `make start` / `make stop` run the stack at https://local.prospecter.io (login `prospector` / `password`)
- `make test` runs the webapp and the library suites (PHPUnit 13)
- `make stan`, `make cs`, `make cs-fix`, `make rector` (dry run) for quality
- `make db` drops and recreates the dev database with fixtures (destroys local data)

Any other command: `docker compose run --rm php <cmd>`. The container mounts the whole repository and its working directory is `webapp/` (e.g. `docker compose run --rm php bin/console debug:router`).

Before declaring work done: `make test`, `make stan` and `make cs` must pass.

## Stack

PHP 8.5, Symfony 8.1, Doctrine ORM 3 on SQLite, AssetMapper + symfonycasts/sass-bundle (no Node), Bootstrap 4 and Font Awesome 4 SCSS sources installed by Composer, nginx 1.30 with a self-signed certificate.

## Code conventions

- Clean code: native types everywhere (typed and promoted readonly properties, return types). No docblock that repeats a native type; docblocks only for what PHP cannot express (`array<string, mixed>`, `list<int>`, `class-string`, generics, `@implements`).
- Do not trust existing docblocks blindly: some were wrong (a `@var stdClass` led Rector to produce a broken type).
- Follow the `@Symfony` php-cs-fixer ruleset; PHPStan level 5.

## Git

- Single branch `main`, linear history (fast-forward or rebase, no merge commits). Use a short-lived branch for big work, then fast-forward `main`.
- Conventional commit messages (`feat:`, `fix:`, `refactor:`, `chore:`), body explaining why.
- Commit or push only when asked.

## Gotchas

- Composer: the library comes from a `path` repository (`../packages/*`). Locally `webapp/vendor/so-lean/cleanprospecter` is a symlink. Never add `"symlink": true` to that repository: it would stop `COMPOSER_MIRROR_PATH_REPOS=1` from copying the package in production builds (see `docs/monorepo.md`).
- Flex recipes edit files you did not ask for (one added a PostgreSQL service to `docker-compose.yml`). Review `git status` after every `composer require`.
- Browser: assets are served by `static.local.prospecter.io`. If pages render without CSS, the self-signed certificate was accepted for the app host but not for the static host (Chrome reports the asset requests as 503). Open a static URL once and accept it.
- Files created in containers run as the host user (UID/GID exported by the Makefile). Root-owned leftovers can be removed with `docker run --rm -v $PWD:/app alpine rm -rf /app/<path>`.
