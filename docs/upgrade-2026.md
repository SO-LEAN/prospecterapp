# 2026 upgrade: what was done and what was learned

The project had not been touched since 2021 (PHP 7.2, Symfony 4.4, webpack-encore on Node 9, Travis). This records the decisions and the traps met while bringing it back, so they are not rediscovered.

## History cleanup

- Branches `master`, `develop`, `travis-ci` and merge commits were replaced by a single linear `main`. `develop` already contained everything; an empty commit was dropped.
- CI was removed (Travis is dead).

## Making the 2021 stack run again (before upgrading)

Dead endpoints broke fresh installs, not the code:

- `cs.sensiolabs.org` (php-cs-fixer download) and `flex.symfony.com` (Flex < 1.21) no longer resolve.
- `symfony/webpack-encore-pack` was deleted from GitHub: Composer could not download it.
- The anonymous GitHub API limit (60 requests/hour) blocks Composer VCS repositories; `"no-api": true` or a token avoids it.
- `docker-compose` v1 is gone, use `docker compose`.
- The 2019 self-signed certificate was expired, 1024-bit and did not cover `static.*`: Chrome silently blocked every asset.

## Upgrade choices

| Topic | Before | After | Why |
|---|---|---|---|
| PHP / Symfony | 7.2 / 4.4 | 8.5 / 8.1 | latest; 8.1 is supported until January 2027, plan the next upgrade |
| Approach | | fresh Symfony 8.1 recipes, then app config ported | cheaper than 4.4 → 5.4 → 6.4 → 7.4 → 8.1 for a small codebase |
| Docker | 3 images | one PHP image (fpm and tooling) + nginx with official templates | |
| Front | webpack-encore, Node | AssetMapper + sass-bundle, ES modules, importmap | no Node at all |
| Bootstrap 4 / FA 4 SCSS | npm | Composer packages `twbs/bootstrap`, `components/font-awesome` | keep the exact look and variable overrides without Node |
| Doctrine mapping | YAML | XML | ORM 3 removed YAML; entities belong to the library, so no attributes |
| Auth | Guard authenticator | `AbstractLoginFormAuthenticator` + Passport, `CustomCredentials` calling the `login` use case | Guard removed in Symfony 6 |
| Tests | PHPUnit 6, Liip | PHPUnit 13, Symfony `WebTestCase`, prophecy-phpunit (since removed: hand-written doubles only) | |
| Env files | `.env.dist` copied to `.env` | committed `.env`, `.env.dev`, `.env.test`, local `.env.local` | Symfony standard |

## Traps

- Doctrine XML mapping namespace is `http://doctrine-project.org/schemas/orm/doctrine-mapping` (http, not https), otherwise validation fails.
- DBAL 4 removed the `array` type: `json` is used. Single table inheritance needs an explicit discriminator map.
- `Request::get()` is removed in Symfony 8; constraint options arrays are replaced by named arguments; `FormTypeInterface` methods are typed.
- The user value resolver only resolves a concrete user class with `#[CurrentUser]`.
- `symfonycasts_sass` options are a fixed list (no `silence_deprecation`); `quiet_deps` hides vendor Sass deprecations.
- AssetMapper resolves CSS `url()` against the real source path: `$fa-font-path` points to `vendor/components/font-awesome/fonts`, which is also an asset path.
- PHP 7.3+ uses PCRE2: `[[:alnum:]-_]` is an invalid range (image URLs were silently broken). `sprintf('%')` throws `ValueError` in PHP 8.
- PHP 8 no longer hides the library's "read the size of a moved file" bug that the stat cache used to mask. Fixed in the library (`StoreFileTrait` reads the metadata before `Storage::add()`).
- Rector `typeDeclarations` turns docblocks into native types: a wrong `@var stdClass` produced a broken property type. Review its output and run the tests.
- PHPStan level 6 requires iterable value docblocks everywhere, which conflicts with the "no type comments" rule; level 5 is used.
- PHPUnit 13: annotations are ignored (use attributes), data providers must be static, abstract classes named `*Test` trigger warnings, `TypeTestCase` mocks need `#[AllowMockObjectsWithoutExpectations]`.
- Flex recipes may add unwanted files or services (a PostgreSQL service in `docker-compose.yml`, `compose.override.yaml`): review after each `composer require`.
- Composer path repository with `"symlink": true` ignores `COMPOSER_MIRROR_PATH_REPOS`: never force it.

# 2026-10-14: from server-rendered pages to SPA + API

The Symfony pages were replaced by a React SPA (`apps/front`) and a JSON API (`apps/api`) with an OAuth 2 server (since moved to `apps/auth`, ADR 0034, then replaced by Cognito, ADR 0052). Choices are explained in `docs/front-architecture.md`, `docs/design-system.md` and `docs/auth.md`.

## Traps

- PHP does not parse `multipart/form-data` on `PUT`/`PATCH`: file uploads are separate `POST .../logo` sub-resources, data endpoints stay JSON.
- The update use cases overwrote every field: `PUT` (full replacement) was the honest verb, and file uploads sent back the current values with the file. Since replaced by one use case and one `PATCH` per intent (ADR 0025).
- PHP does not allow `new` in class constants (only in parameter defaults and attribute arguments).
- Symfony refuses to redirect to another host after logout (falls back to `/`): `LogoutListener` sets the response explicitly.
- `KernelBrowser` reboots the kernel between requests: the OAuth bundle's in-memory persistence loses the authorization code; tests use Doctrine persistence.
- NelmioApiDocBundle documents `{id}` path parameters as strings: the front converted ids with `String(id)` (obsolete since the ids are UUID strings).
- Two existing bugs surfaced by the API tests: removing a logo passed its URL to `unlink()` (nothing was deleted), and a failed geocoding was stored as 0,0 (the adapter dropped the "succeeded" flag).
- TypeScript 7 is native and has no JavaScript compiler API: `openapi-typescript` runs with `pnpm dlx` and TypeScript 5.
- pnpm 12 ignores the `pnpm` field of `package.json` (settings live in `pnpm-workspace.yaml`) and ignores `PNPM_STORE_DIR` (use `npm_config_store_dir`); otherwise the store lands at the root of the mounted repository.
- `@vitejs/plugin-react` 6: React Compiler through `@rolldown/plugin-babel` + `reactCompilerPreset()` (the Rust compiler option is still experimental).
- TanStack Router: calling `router.invalidate()` before `RouterProvider` has received its context runs the guards with an undefined context; and right after the OAuth callback, the `useAuth()` state lags, so the guard reads `userManager.getUser()`.
- shadcn's generator wrote `import { cn } from "cn"` with a custom `utils` alias, and its sonner wrapper imports `next-themes`.
- Playwright in Docker reaches the stack with `--network host` (nginx listens on 127.0.0.1 only) and `--add-host` for the three local hosts.

# 2026-10-15: bounded contexts, authorization server, storage

## Traps

- Doctrine refuses to initialize a lazy proxy whose identity is a readonly value object (hydration would replace it with an equal but new one): no association, no `getReference()`; API tests empty the entity manager after loading the fixtures.
- SQLite keeps no time zone, not even with `datetimetz_immutable`: a moment read back lands in the time zone of the server. The `moment` Doctrine type writes and reads in UTC.
- Without `symfony/monolog-bundle`, Symfony's default logger writes errors to stderr, so expected errors pollute the test output.
- Messenger routes a message by the type of the argument of `__invoke()`: a generic decorator taking `object` needs the `handles` attribute of its tag (`RefusalTranslatingHandlersPass`).
- Rector and PHPStan can disagree (`?->` before `??`, `$handler->__invoke(...)` on a marker interface): write the code so that both accept it rather than ignoring one.
- The generated front types of a second contract must be excluded from Biome like the first one.
- A Symfony array cache is cleared between two requests of a functional test (services are reset): the sign-in throttling cannot count attempts in it. The tests keep the default cache and sign in from an address of their own.
- A lazy reference of the fixtures left in the entity manager breaks the next request that loads the same aggregate (its readonly identity): read the identity, then clear the entity managers (`ApiTestCase::accountOfTheFixtures()`).
- The first target of a Makefile is its default: `install` ran on a bare `make` and recreated the databases while the e2e journeys ran. `help` is the default now.

# 2026-10-16: the files on S3, the sign-in on Cognito, MiniStack locally

The storage keeps its files in an S3 bucket (ADR 0051); Cognito signs the members in and `apps/auth` leaves the repository with Doctrine (ADR 0052).

## Traps

- MiniStack keeps the S3 objects in `/tmp` of its container even with `PERSIST_STATE`: `S3_PERSIST` and `S3_DATA_DIR` put them in its volume.
- MiniStack answers every request with its own CORS headers: nginx hides them where it adds its own, or the browser sees `Access-Control-Allow-Origin` twice.
- nginx resolves the host of a `proxy_pass` once at start: through a variable and the resolver of Docker (`127.0.0.11`), MiniStack may be recreated without a 502.
- Symfony writes the directives of `Cache-Control` in its own order: a test checks the directives, not the string.
- MiniStack makes the ids of a user pool and of a client at random (no custom id): the API finds them by name.
- MiniStack applies the password policy of the pool (uppercase, digits and symbols by default), but neither keeps an email alias unique nor asks for a code when it changes: the API checks the uniqueness itself.
- OpenSSL cannot build an RSA public key from the modulus and exponent of a JWK alone: `RsaPublicKey` encodes its DER.
- An access token of Cognito has no `nbf`: lcobucci's `StrictValidAt` refuses it; `LooseValidAt` and a constraint requiring `exp` check it.
- The clock of the API tests is fixed in the past, the tokens of MiniStack are issued now: the tokens are checked with a clock of their own (`app.token_clock`).
- `amazon-cognito-identity-js` reads the Node `global`: Vite defines it as `globalThis`, or the page stays blank.
- The SPA, served in https, cannot call MiniStack in http (mixed content): nginx passes `/cognito/` of the API host to it.

# 2026-10-16: the API in a local Lambda with the Bref layer

`make lambda` runs the API in a Lambda with the layer of Bref on MiniStack, behind an HTTP API, the repository mounted (ADR 0053).

## Traps

- MiniStack runs a function in a container only with `LAMBDA_EXECUTOR=docker` and the socket of the Docker of the host; it copies the code into `/var/task` (a bind mount of its own temporary folder would be empty for the Docker of the host) and the layers into `/opt`.
- MiniStack starts every `provided` runtime with the handler `bootstrap`: Bref then looks for `/var/task/bootstrap`. The local `bootstrap` gives Bref its handler back.
- Bref compiles the folder of its `.ini` files (`/opt/bref/etc/php/conf.d:/var/task/php/conf.d`): with the code elsewhere, `PHP_INI_SCAN_DIR` points to it, or `intl` stays off.
- Bref 3 has one layer per PHP version (`php-85`) for every runtime, chosen by `BREF_RUNTIME`; `intl` is shipped in `/opt/bref/extensions` but disabled.
- The function runs as root and `LAMBDA_DOCKER_FLAGS` takes no `--user`: a `umask 0000` keeps what it writes in the mounted repository writable by the php container.
- MiniStack answers every preflight of an HTTP API from its CORS (403 "CORS not configured" without one), where AWS passes it to the integration when none is configured.
- Bref does not tell Symfony the request was https: behind the gateway, `X-Forwarded-Proto` is trusted (`TRUSTED_PROXIES`).
- `$default` in an nginx config is a variable, and envsubst eats it in a template: the stage is named `local`.
- `fastcgi_pass` or `proxy_pass` with a variable needs a `resolver`.
- A recipe of Flex (`nyholm/psr7`, required by Bref) adds a configuration nobody asked for: review `git status` after `composer require`.

# 2026-10-16: the storage in a local Lambda

`make lambda` runs the storage in a Lambda as well, with the layer of imagick of Bref beside the one of PHP (ADR 0054).

## Traps

- Every app in a Bref Lambda needs `bref/bref` in its own `composer.json`: the layer starts `Bref\FpmRuntime\Main` from the vendor of the code, and says so in the logs of the function only (the gateway answers a bare 502).
- The extension images of Bref (`bref/extra-imagick-php-85`) hold nothing but their `/opt`, no shell: read them with `docker create` and `docker cp`.
- Bref sends a body base64-encoded, as the gateway needs for an image, only with `BREF_BINARY_RESPONSES=1`.
- `make lambda` and `make fpm` recreate MiniStack: for a few seconds it refuses connections (nginx answers 502).

# 2026-10-16: the Lambdas are the only local runtime

PHP-FPM leaves the local stack: `make start` deploys the API and the storage in their Lambdas, the `php` container is the command line only (ADR 0055).

## Traps

- A service in a profile (`php`, profile `cli`) is not started by `docker compose up`, but `docker compose run php` still runs it; a container of it started before stays up until removed (`docker compose rm -sf php`).

