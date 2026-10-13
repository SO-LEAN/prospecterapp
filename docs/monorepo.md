# Monorepo

```
webapp/                    Symfony application
packages/cleanprospecter/  so-lean/cleanprospecter library
docker/  docker-compose.yml  Makefile  .github/workflows/split.yml
```

## How the webapp uses the library

`webapp/composer.json`:

```json
"repositories": [{ "type": "path", "url": "../packages/*" }],
"require": { "so-lean/cleanprospecter": "^1.0@dev" }
```

- Path repositories are checked before Packagist, so the package always comes from `packages/`.
- By default Composer installs it as a symlink: `webapp/vendor/so-lean/cleanprospecter -> ../../../packages/cleanprospecter`. Changes to the library are visible immediately, without `composer update` (like npm/pnpm workspaces).
- Version: a path package has no tag, Composer names it after the current Git branch (`dev-main`). The library declares `"branch-alias": {"dev-main": "1.x-dev"}`, so `^1.0` matches it, and `@dev` allows this dev version although the project is `minimum-stability: stable`. This constraint is a range: it never needs to be bumped. Only a new major version would change the alias and the constraint.
- The whole repository is mounted in the PHP container, otherwise the symlink would point nowhere.

## Production builds

A symlink to `../packages` cannot be shipped. Build from the repository root (so `packages/` is in the build context) with:

```bash
cd webapp
COMPOSER_MIRROR_PATH_REPOS=1 composer install --no-dev --classmap-authoritative
```

Composer then copies the current content of `packages/cleanprospecter` into `vendor/` (even if `composer.lock` is behind), and `webapp/` becomes self-contained: deploy only `webapp/`. This only works because the path repository does not force `"symlink": true`; keep it that way.

The copy includes the library `tests/` (`.gitattributes export-ignore` only affects Packagist archives); remove `vendor/so-lean/cleanprospecter/tests` in the image if wanted.

## Publishing on Packagist

`so-lean/cleanprospecter` is registered on Packagist and reads `SO-LEAN/cleanprospecter`. That repository is now a read-only mirror fed by `.github/workflows/split.yml` (`symplify/monorepo-split-github-action`):

- push to `main` → branch `main` of the mirror → `1.x-dev` on Packagist
- tag `v*` (e.g. `v1.0.0`) → same tag on the mirror → stable version on Packagist

The workflow needs the `SPLIT_ACCESS_TOKEN` secret in `SO-LEAN/prospecterapp`: a fine-grained token with Contents read/write on `SO-LEAN/cleanprospecter`. Other branches of the mirror (`develop`, `master`, `claude/refactor-ddd-architecture-UJMig`) are left untouched.

Tags are only needed for external consumers; the webapp never uses them.

## Why not a separate package for the domain or the adapters

The library already is the hexagon and its `composer.json` only requires `php`, which enforces the boundary. Splitting entities from use cases, or extracting the Doctrine adapters into a bridge package, only pays off once a second consumer (API, CLI app) needs them.
