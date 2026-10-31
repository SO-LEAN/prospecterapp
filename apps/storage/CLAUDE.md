# storage (Symfony 8.1)

@AGENTS.md

The generic Symfony guidance above is overridden by the project rules below when they differ. No Symfony CLI: run commands through `docker compose run --rm php ...` (see the root `CLAUDE.md`).

## Role

Keeps the files uploaded through the API in S3 buckets and serves them, the images in named sizes made on first request; keeps the private files of the exchanges, sent and read by signed requests. No business rule, no database.

## What is in force

Read before working on it: `docs/architecture/storage.md` (buckets, hosts, entries, private files, layout of the bucket), `docs/architecture/local-stack.md` (the storage in its Lambda), `docs/architecture/infra.md` (on AWS), `docs/architecture/tests.md` (its tests).

## Commands

From the repository root: `make test-storage` (starts MiniStack); `make lint` and `make fix` cover it. One command: `docker compose run --rm --no-deps -w /var/www/src/apps/storage php <cmd>`.
