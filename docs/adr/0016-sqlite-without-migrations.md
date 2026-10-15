# 0016. SQLite, schema generated from the mapping, no migrations

- Date: 2026-10-13
- Status: Accepted (to revisit before any production deployment)
- Implemented by: `DATABASE_URL` in `apps/api/.env`, `make db`, `apps/api/CLAUDE.md`

## Context

The application has no production deployment and its model changes often during the refactoring.

## Decision

SQLite file database. The schema is created from the Doctrine mapping (`make db` recreates it with fixtures, tests recreate it for each test); there are no migrations. A local database with data worth keeping is updated with `doctrine:schema:update` after reviewing the SQL.

## Consequences

- Changing the model costs nothing while there is no production data.
- No history of schema changes; `schema:update` can drop columns.
- SQLite differs from a production database on concurrency and some SQL behaviours.

## Alternatives considered

- PostgreSQL with Doctrine migrations from now on: right for production, overhead while the model is still moving.

## Revisit when

Before the first production deployment: choose the production database and start migrations from the schema of that day.
