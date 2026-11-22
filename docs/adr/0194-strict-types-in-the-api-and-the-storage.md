# 0194. Strict types in the API and the storage

- Date: 2026-11-22
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/.php-cs-fixer.dist.php`, `apps/storage/.php-cs-fixer.dist.php`, `apps/api/.gitignore`, `apps/storage/.gitignore`, every PHP file of `apps/api` and `apps/storage`, `docs/architecture/clean-code.md` ("PHP and TypeScript specifics"), `apps/api/CLAUDE.md`, `apps/storage/CLAUDE.md`

## Context

Since ADR 0018 the core declares `strict_types`, and its php-cs-fixer adds the declaration. The API and the storage disabled the rule (`'declare_strict_types' => false`) without a written reason: 13 of the API's 699 files in `src/` declared it, none of the storage's. An implicit conversion of a scalar passed there silently, at the boundaries first (HTTP payloads, DynamoDB items, environment variables, the AWS SDK's answers).

## Decision

- Every PHP file of `apps/api` and `apps/storage` declares `declare(strict_types=1)`: `src/`, `tests/`, `public/`, `config/bundles.php`, `config/preload.php`, `rector.php`, `bin/console` and `bin/phpunit`.
- Their php-cs-fixer sets `declare_strict_types` to `true`, checks `bin/console` and `bin/phpunit` too and skips the files git ignores, so `make lint` refuses a file without the declaration.
- `config/bundles.php` is linted like any other file: when Flex rewrites it without the declaration at a recipe, `make lint` says so and `make fix` puts it back.
- `config/reference.php` is no longer committed (`.gitignore` of each app): Symfony writes it again, without the declaration, at each build of the debug container, and the configuration of the apps is in YAML, which does not read it.

## Consequences

- A scalar of the wrong type fails with a `TypeError` instead of being converted. The API tests, the storage tests and PHPStan found no conversion to fix when the declaration was added; a value a library gives untyped is still checked where it is taken.
- The API's PHPStan baseline did not change.
- An editor no longer finds `config/reference.php` in a fresh clone: it appears at the first build of the debug container (`make test-api`, the stack).

## Alternatives considered

- Exempting `config/bundles.php` and `config/reference.php` as generated files: the maintainer refused, the apps' files are all clean.
- Linting `config/reference.php` like `config/bundles.php`: Symfony takes the declaration out at each build of the debug container (each test run, each request of the stack), so `make lint` failed after any of them.

## Revisit when

A file the apps need cannot run under strict types.
