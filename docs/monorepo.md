# Monorepo

```
apps/front/      React SPA (pnpm, its own package.json)
apps/api/        Symfony API (Composer)
packages/core/   prospecter/core library (Composer)
docker/  docker-compose.yml  Makefile  docs/
```

The two apps are independent projects with their own package manager; they only share the HTTP contract (`apps/api/openapi.json`, from which the front generates its types) and the root tooling (Docker, Makefile).

## How the API uses the library

`apps/api/composer.json`:

```json
"repositories": [{ "type": "path", "url": "../../packages/*" }],
"require": { "prospecter/core": "*@dev" }
```

- Path repositories are checked before Packagist, so the package always comes from `packages/` (the old 2021 version on Packagist is never used).
- By default Composer installs it as a symlink: `apps/api/vendor/prospecter/core -> ../../../../packages/core`. Changes to the library are visible immediately, without `composer update` (like npm/pnpm workspaces).
- Version: a path package has no tag, Composer names it after the current Git branch (`dev-main`). `*` accepts any version and `@dev` allows this dev version although the project is `minimum-stability: stable`. Nothing is ever bumped.
- The whole repository is mounted in the PHP container, otherwise the symlink would point nowhere.

## Production builds

A symlink to `../../packages` cannot be shipped. Build from the repository root (so `packages/` is in the build context) with:

```bash
cd apps/api
COMPOSER_MIRROR_PATH_REPOS=1 composer install --no-dev --classmap-authoritative
```

Composer then copies the current content of `packages/core` into `vendor/` (even if `composer.lock` is behind), and `apps/api/` becomes self-contained: deploy only `apps/api/`. This only works because the path repository does not force `"symlink": true`; keep it that way.

The copy includes what the core needs for its own tests (`vendor/`, `tests/`, `features/`, tool configs): remove `vendor/prospecter/core/{vendor,tests,features}` in the production image.

The front builds to static files (`pnpm build` → `apps/front/dist/`), served by any web server with a fallback to `index.html`; its API URL, app URL and OAuth client id are `VITE_*` variables set at build time.

## Why not a separate package for the domain or the adapters

The library already is the hexagon and its `composer.json` only requires `php`, which enforces the boundary. Splitting entities from use cases, or extracting the Doctrine adapters into a bridge package, only pays off once a second consumer (API, CLI app) needs them.

## Decisions

- 2026-10-13: the library is no longer published on Packagist. Nobody outside this repository installs it, so the split workflow to `SO-LEAN/cleanprospecter`, the `1.x-dev` branch alias and the `^1.0@dev` constraint were removed (`*@dev` now). The package and the `path` repository are kept on purpose: the library's own `composer.json` (PHP only) keeps the hexagon free of framework dependencies, and production builds can copy it into `apps/api/vendor` to ship `apps/api/` alone. To publish again, restore a split workflow (e.g. `symplify/monorepo-split-github-action`) pushing `packages/core` to `SO-LEAN/cleanprospecter`, which Packagist already tracks.
- 2026-10-14: `packages/cleanprospecter` renamed to `packages/core` (Composer package `prospecter/core`, namespace `Prospecter\Core\`) and `webapp/` moved to `apps/api/`, next to the React SPA in `apps/front/`.
