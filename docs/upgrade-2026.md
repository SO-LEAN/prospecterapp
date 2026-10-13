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
| Tests | PHPUnit 6, Liip | PHPUnit 13, Symfony `WebTestCase`, prophecy-phpunit | |
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
