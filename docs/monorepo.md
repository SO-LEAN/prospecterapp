# Monorepo

```
apps/front/      React SPA (pnpm, its own package.json)
apps/api/        Symfony API, resource server of the Cognito user pool (Composer), the only user of the core
apps/storage/    Symfony file storage (Composer)
packages/core/   prospecter/core library (Composer)
tools/phpstan/   PHPStan rules shared by the three PHP codebases (autoload-dev of each, ADR 0044)
tools/git-hooks/ the pre-commit hook (gitleaks, ADR 0059)
apps/*/infra/    the Terraform of each app on AWS (docs/infra.md, ADR 0059)
docker/  docker-compose.yml  Makefile  docs/
```

The apps are independent projects with their own dependencies; they only share HTTP contracts (`apps/api/openapi.json`, from which the front generates its types; the internal `POST/DELETE /files` of the storage) and the AWS services (MiniStack locally: the Cognito user pool the SPA signs in with and whose tokens the API checks) and the root tooling (Docker, Makefile). Only the API uses the core.

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

Composer then copies the current content of `packages/core` into `vendor/` (even if `composer.lock` is behind), and `apps/api/` becomes self-contained: deploy it alone. `apps/storage/` does not use the core; `make package-storage` packages it the same way (`build/storage.zip`). This only works because the path repository does not force `"symlink": true`; keep it that way.

The copy includes what the core needs for its own tests (`vendor/`, `tests/`, `features/`, tool configs): remove `vendor/prospecter/core/{vendor,tests,features}` in the production image. `make package-api` / `make package-storage` do all of it for the Lambdas on AWS (`tools/package/lambda`, `build/<app>.zip`).

The front builds to static files (`pnpm build` → `apps/front/dist/`), one build for every instance: its API URL comes from `/config.js` (on AWS answered by the CloudFront Function of the instance, ADR 0066), else from the `VITE_*` variables of the build; on AWS each build is a release under `releases/<version>/` of its bucket (`make package-front`, `make release-front`); where to sign in, it asks the API (`GET /api/user-pool`).

## Why not a separate package for the domain or the adapters

The library already is the hexagon and its `composer.json` only requires `php`, `ext-intl` and the pure PHP `giggsey/libphonenumber-for-php` (ADR 0072), which enforces the boundary. Splitting entities from use cases, or extracting the persistence adapters into a bridge package, only pays off once a second consumer (API, CLI app) needs them.

## Decisions

- 2026-10-13: the library is no longer published on Packagist. Nobody outside this repository installs it, so the split workflow to `SO-LEAN/cleanprospecter`, the `1.x-dev` branch alias and the `^1.0@dev` constraint were removed (`*@dev` now). The package and the `path` repository are kept on purpose: the library's own `composer.json` (PHP only) keeps the hexagon free of framework dependencies, and production builds can copy it into `apps/api/vendor` to ship `apps/api/` alone. To publish again, restore a split workflow (e.g. `symplify/monorepo-split-github-action`) pushing `packages/core` to `SO-LEAN/cleanprospecter`, which Packagist already tracks.
- 2026-10-14: `packages/cleanprospecter` renamed to `packages/core` (Composer package `prospecter/core`, namespace `Prospecter\Core\`) and `webapp/` moved to `apps/api/`, next to the React SPA in `apps/front/`.
