# Monorepo

```
webapp/                    Symfony application
packages/cleanprospecter/  so-lean/cleanprospecter library
docker/  docker-compose.yml  Makefile
```

## How the webapp uses the library

`webapp/composer.json`:

```json
"repositories": [{ "type": "path", "url": "../packages/*" }],
"require": { "so-lean/cleanprospecter": "*@dev" }
```

- Path repositories are checked before Packagist, so the package always comes from `packages/` (the old 2021 version on Packagist is never used).
- By default Composer installs it as a symlink: `webapp/vendor/so-lean/cleanprospecter -> ../../../packages/cleanprospecter`. Changes to the library are visible immediately, without `composer update` (like npm/pnpm workspaces).
- Version: a path package has no tag, Composer names it after the current Git branch (`dev-main`). `*` accepts any version and `@dev` allows this dev version although the project is `minimum-stability: stable`. Nothing is ever bumped.
- The whole repository is mounted in the PHP container, otherwise the symlink would point nowhere.

## Production builds

A symlink to `../packages` cannot be shipped. Build from the repository root (so `packages/` is in the build context) with:

```bash
cd webapp
COMPOSER_MIRROR_PATH_REPOS=1 composer install --no-dev --classmap-authoritative
```

Composer then copies the current content of `packages/cleanprospecter` into `vendor/` (even if `composer.lock` is behind), and `webapp/` becomes self-contained: deploy only `webapp/`. This only works because the path repository does not force `"symlink": true`; keep it that way.

The copy includes the library `tests/`; remove `vendor/so-lean/cleanprospecter/tests` in the image if wanted.

## Why not a separate package for the domain or the adapters

The library already is the hexagon and its `composer.json` only requires `php`, which enforces the boundary. Splitting entities from use cases, or extracting the Doctrine adapters into a bridge package, only pays off once a second consumer (API, CLI app) needs them.

## Decisions

- 2026-10-13: the library is no longer published on Packagist. Nobody outside this repository installs it, so the split workflow to `SO-LEAN/cleanprospecter`, the `1.x-dev` branch alias and the `^1.0@dev` constraint were removed (`*@dev` now). The package and the `path` repository are kept on purpose: the library's own `composer.json` (PHP only) keeps the hexagon free of framework dependencies, and production builds can copy it into `webapp/vendor` to ship `webapp/` alone. To publish again, restore a split workflow (e.g. `symplify/monorepo-split-github-action`) pushing `packages/cleanprospecter` to `SO-LEAN/cleanprospecter`, which Packagist already tracks.
