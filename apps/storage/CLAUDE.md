# storage (Symfony 8.1)

No Symfony CLI: run commands through `docker compose run --rm php ...` (see `docs/architecture/commands.md`).

## Role

Keeps the files uploaded through the API in S3 buckets and serves them, with images in named sizes generated on first request. It also keeps the exchanges' private files, uploaded and read through signed requests. No business rules, no database.

## What is in force

Read before working on the storage: `docs/architecture/storage.md` (buckets, hosts, entry points, private files, bucket layout), `docs/architecture/local-stack.md` (the storage in its Lambda), `docs/architecture/infra.md` (on AWS), `docs/architecture/tests.md` (its tests).

## Commands

From the repository root: `make test-storage` (starts MiniStack); `make lint` and `make fix` cover it too (PHPStan at level `max` with no baseline, ignoring only `missingType.iterableValue`, ADR 0192: what the AWS SDK gives untyped is read through `Typed`). To run a single command: `docker compose run --rm --no-deps -w /var/www/src/apps/storage php <cmd>`.
