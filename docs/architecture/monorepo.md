# Monorepo

This document states the rules in force. The decision records (`docs/adr`) explain why: read them only for that, or before changing a rule (ADR 0090).

```
apps/front/      React SPA (pnpm, its own package.json)
apps/api/        Symfony API, resource server of the Cognito user pool (Composer), the only user of the core
apps/storage/    Symfony file storage (Composer)
packages/core/   prospecter/core library (Composer)
packages/phpstan-rules/ PHPStan rules shared by the three PHP codebases (autoload-dev of each, ADR 0044), and the event storming's generator (EventStorming/, its tests and fixtures in tests/EventStorming/, docs/architecture/event-storming.md)
apps/*/infra/    each app's Terraform on AWS (docs/architecture/infra.md, ADR 0059)
make/            the fragments the Makefiles include (ADR 0080)
.githooks/       the pre-commit hook (gitleaks, ADR 0059), installed by make hooks
docker/  docker-compose.yml  Makefile  docs/
```

Whatever a project does is a recipe in its Makefile. Whatever several projects share is a fragment in `make/` (ADR 0080, 0083). The only scripts left are files that a tool runs, each next to what uses it:

| Script | Run by | Why not a recipe |
|---|---|---|
| `.githooks/pre-commit` | git, before each commit | git runs a file from `core.hooksPath` |
| `docker/lambda/bootstrap` | the local functions, when they start (ADR 0055) | a function's runtime runs a file |
| `docker/lambda/deploy.sh` | the `lambda-deploy` image (`make deploy`) | an image's entrypoint |
| `docker/nginx/05-generate-certificate.sh` | the nginx image, when it starts | an image's entrypoint |
| `apps/front/script/release` | the front's `make release`, in the AWS CLI image | that image has no make, and cleaning up old releases is a loop over the bucket |

The apps are independent projects with their own dependencies. They share only:

- HTTP contracts: `apps/api/openapi.json`, from which the front generates its types, and the storage's internal `POST/DELETE /files`;
- the AWS services (MiniStack locally): the Cognito user pool the SPA signs in with, whose tokens the API checks;
- the root tooling: Docker and the fragments of `make/`.

Only the API uses the core. [ddd.md](ddd.md), "The core as a package, a legacy", says why the core is a package of its own and what it costs.

Each project has its own `Makefile`. You run it from the project's directory (`cd apps/api && make test`), and `make` alone lists its commands. It includes only the fragments of `make/`. The root `Makefile` is the façade: its targets run the projects' targets (`make test-api` runs `make -C apps/api test`) and hold only what spans several projects (`install`, `ci`, the stack, `api-doc`, the secrets). A project never runs another project's Makefile (ADR 0080).

A PHP project's lint (`make/php.mk`: PHPStan, php-cs-fixer and Rector, each in its own container) keeps its caches in the project's ignored `var/cache/` (`tmpDir` in `phpstan.dist.neon`, `withCache` in `rector.php`, `setCacheFile` in `.php-cs-fixer.dist.php`). The caches last from one run to the next, and each checkout has its own: a container's `/tmp` disappears with it.

- PHPStan analyses the whole project even with `FILTER`, because a class reads its properties in traits it may not be given. It uses its result cache. The cache analyses a file again when that file, a class it names, the configuration or a rule changes. A rule of `packages/phpstan-rules` that reads a class the file does not name (a command's handler, the dependencies of a built class) declares that class to the cache (`trackClassDependency()` on the scope). Otherwise the file keeps a stale result, and `make lint` passes where the CI's fresh analysis fails. PHPStan runs with its own memory limit (`PHPSTAN_MEMORY_LIMIT`): 1G for its main process and for each of its workers. That is above the command line's 256M (`docker/php/php.ini`). One of the API's workers outgrew 256M on a CI runner, depending on the files it was given. Analysed in one process, the whole API peaks at about 360M, and the core at about 270M.
- With `FILTER`, Rector runs in one process (`RECTOR_IN_ONE_PROCESS`, read by `rector.php`): a worker takes as long to start as it takes to check a few files. On the whole project, it runs as many workers as the container has processors.
- `make lint` runs a project's three checks together, as `make ci` does: linting after changing one file takes about three seconds.

The Lambda package leaves out the `var/` of the app and of the core it copies in. CI runners start from nothing, so every lint there starts cold.

## The AWS SDK services

The API and the storage keep only the AWS SDK services listed in their `composer.json` (`extra.aws/aws-sdk-php`). At each autoload dump, the script `Aws\Script\Composer\Composer::removeUnusedServices` deletes the others from `vendor`. `composer install` does not bring back a service added to the list, because the SDK is already installed at its version. The app then fails at startup (`Service id "Aws\Sqs\SqsClient" looks like a FQCN but no corresponding class or interface exists`). These apps' `make vendor` (`make/composer.mk`, run by `make install` and `make ci`) first reinstalls the SDK when a listed service has no directory in `vendor/aws/aws-sdk-php/src`. When the list did not change, it reinstalls nothing. The Lambda package (`make/lambda.mk`) installs a fresh `vendor`, so it always has the listed services.

## How the API uses the library

`apps/api/composer.json`:

```json
"repositories": [{ "type": "path", "url": "../../packages/*" }],
"require": { "prospecter/core": "*@dev" }
```

- Composer checks path repositories before Packagist, so the package always comes from `packages/` (the old 2021 version on Packagist is never used).
- By default, Composer installs it as a symlink: `apps/api/vendor/prospecter/core -> ../../../../packages/core`. Changes to the library are visible immediately, without `composer update` (like npm/pnpm workspaces).
- Version: a path package has no tag, so Composer names it after the current Git branch (`dev-main`). `*` accepts any version, and `@dev` allows this dev version although the project is `minimum-stability: stable`. Nothing is ever bumped.
- The whole repository is mounted in the PHP container; otherwise the symlink would point nowhere.

## Production builds

A symlink to `../../packages` cannot be shipped. Build from the repository root (so that `packages/` is in the build context) with:

```bash
cd apps/api
COMPOSER_MIRROR_PATH_REPOS=1 composer install --no-dev --classmap-authoritative
```

Composer then copies the current content of `packages/core` into `vendor/` (even if `composer.lock` is behind), and `apps/api/` becomes self-contained: deploy it alone. `apps/storage/` does not use the core. `make package-storage` packages it the same way (`build/storage.zip`). This only works because the path repository does not force `"symlink": true`: keep it that way.

The copy includes what the core needs for its own tests (`vendor/`, `tests/`, `features/`, tool configs): remove `vendor/prospecter/core/{vendor,tests,features,Makefile}` in the production image. `make package-api` / `make package-storage` do all of this for the Lambdas on AWS (`make/lambda.mk`, `build/<app>.zip`).

The front builds to static files (`pnpm build` → `apps/front/dist/`), one build for every instance. Its API URL comes from `/config.js` (on AWS, the instance's CloudFront Function answers it, ADR 0066), or else from the build's `VITE_*` variables. On AWS, each build is a release under `releases/<version>/` in its bucket (`make package-front`, `make release-front`). To know where to sign in, the front asks the API (`GET /api/user-pool`).

## Why not a separate package for the domain or the adapters

The library already is the hexagon. Its `composer.json` only requires `php`, `ext-intl` and the pure PHP `giggsey/libphonenumber-for-php` (ADR 0072), which enforces the boundary. Splitting entities from use cases, or extracting the persistence adapters into a bridge package, only pays off once a second consumer (an API, a CLI app) needs them.

## Decisions

- 2026-10-13: the library is no longer published on Packagist. Nobody outside this repository installs it, so we removed the split workflow to `SO-LEAN/cleanprospecter`, the `1.x-dev` branch alias and the `^1.0@dev` constraint (now `*@dev`). We kept the package and the `path` repository on purpose. The library's own `composer.json` (PHP only) keeps the hexagon free of framework dependencies. Production builds can copy it into `apps/api/vendor` to ship `apps/api/` alone. To publish again, restore a split workflow (e.g. `symplify/monorepo-split-github-action`) that pushes `packages/core` to `SO-LEAN/cleanprospecter`, which Packagist already tracks.
- 2026-10-14: `packages/cleanprospecter` was renamed to `packages/core` (Composer package `prospecter/core`, namespace `Prospecter\Core\`), and `webapp/` moved to `apps/api/`, next to the React SPA in `apps/front/`.
