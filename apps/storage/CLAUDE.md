# storage (Symfony 8.1)

No Symfony CLI: run commands through `docker compose run --rm php ...` (see `docs/architecture/commands.md`).

## Role

The storage keeps the files uploaded through the API in S3 buckets and serves them. It generates images in named sizes on first request. It also keeps the exchanges' private files, which are uploaded and read through signed requests. It has no business rules and no database.

## What is in force

Read before working on the storage: `docs/architecture/storage.md` (buckets, hosts, entry points, private files, bucket layout), `docs/architecture/local-stack.md` (the storage in its Lambda), `docs/architecture/infra.md` (on AWS), `docs/architecture/tests.md` (its tests).

## Commands

Run them from the repository root. `make test-storage` runs the tests and starts MiniStack. `make lint` and `make fix` cover the storage too. PHPStan runs at level `max` with no baseline and ignores only `missingType.iterableValue` (ADR 0192). What the AWS SDK gives untyped is read through `Typed`. Every PHP file declares `declare(strict_types=1)`, `bin/console` included. `make lint` refuses a file without it, `config/bundles.php` included. `make fix` puts the declaration back after Flex removes it. Symfony writes `config/reference.php` at each build of the debug container. That file is not committed (ADR 0194). To run a single command: `docker compose run --rm --no-deps -w /var/www/src/apps/storage php <cmd>`.
