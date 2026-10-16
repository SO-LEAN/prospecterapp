# 0016. SQLite, schema generated from the mapping, no migrations

- Date: 2026-10-13
- Status: Superseded by [0048](0048-dynamodb-one-table-per-context-on-ministack.md) (the API) and [0052](0052-cognito-signs-the-members-in.md) (the auth app removed)
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

None were weighed: this is the state inherited from the upgrade, recorded so that it is revisited before production.

## Revisit when

Before the first production deployment: choose the production database and start migrations from the schema of that day.
