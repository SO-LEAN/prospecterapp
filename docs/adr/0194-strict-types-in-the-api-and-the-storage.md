# 0194. Strict types in the API and the storage

- Date: 2026-11-22
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/.php-cs-fixer.dist.php`, `apps/storage/.php-cs-fixer.dist.php`, every PHP file of `apps/api` and `apps/storage`, `docs/architecture/clean-code.md` ("PHP and TypeScript specifics"), `apps/api/CLAUDE.md`, `apps/storage/CLAUDE.md`

## Context

Since ADR 0018 the core declares `strict_types`, and its php-cs-fixer adds the declaration. The API and the storage disabled the rule (`'declare_strict_types' => false`) without a written reason: 13 of the API's 699 files in `src/` declared it, none of the storage's. An implicit conversion of a scalar passed there silently, at the boundaries first (HTTP payloads, DynamoDB items, environment variables, the AWS SDK's answers).

## Decision

- Every PHP file of `apps/api` and `apps/storage` declares `declare(strict_types=1)`: `src/`, `tests/`, `public/`, `config/preload.php`, `rector.php`, `bin/console` and `bin/phpunit`.
- Their php-cs-fixer sets `declare_strict_types` to `true` and checks `bin/console` and `bin/phpunit` too, so `make lint` refuses a file without the declaration.
- Exempt: `config/bundles.php`, which Flex rewrites at each recipe, and `config/reference.php`, which Symfony generates. Both were already out of php-cs-fixer's finder.

## Consequences

- A scalar of the wrong type fails with a `TypeError` instead of being converted. The API tests, the storage tests and PHPStan found no conversion to fix when the declaration was added; a value a library gives untyped is still checked where it is taken.
- The API's PHPStan baseline did not change.

## Alternatives considered

None.

## Revisit when

A file the apps need cannot run under strict types.
