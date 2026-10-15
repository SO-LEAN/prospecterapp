# 0038. One database per bounded context

- Date: 2026-10-15
- Status: Accepted
- Implemented by: `apps/api/config/packages/doctrine.yaml`, the Doctrine repositories of `apps/api/src/<Context>/Persistence`, `apps/api/tests/Architecture/DatabasePerContextTest.php`

## Context

The bounded contexts (0030, 0031) shared one SQLite database: nothing kept a query from joining the tables of two contexts. The user wanted a schema per context to segment the data. SQLite has no schemas (`ATTACH DATABASE` comes closest, and Doctrine DBAL 4 neither supports nor emulates schemas on SQLite). The user plans SQLite for local development and DynamoDB in production.

## Decision

- Each context of the API (Accounts, PersonalData, Prospecting) has its own database, with its own Doctrine connection and entity manager (`accounts`, `personal_data`, `prospecting`); locally a SQLite file each (`var/<context>_<env>.db`), configured by `<CONTEXT>_DATABASE_URL`.
- A repository receives the entity manager of its context by name (`$prospectingEntityManager`); nothing outside the persistence adapters reaches a database. `DatabasePerContextTest` guards both.
- The schema is still generated from the mapping (0016), per entity manager.

## Consequences

- No join, foreign key or transaction across two contexts: the rule of 0030 (one aggregate per use case, contexts linked by identity) is now physical.
- Each context can move to another store on its own: a DynamoDB adapter per repository, behind the same ports of the core, without touching the other contexts.
- Three databases to create and reset (`make db`, `ApiTestCase`).
- The queries written for SQL (`pageProspectedBy`: a search on the corporate name or the email, newest first) will need another design on DynamoDB (keys and indexes chosen for these accesses).

## Alternatives considered

- A prefix of the table names per context in one database: a convention nothing enforces.
- PostgreSQL and its schemas: a database server to run, against 0016 and the DynamoDB target.
- `ATTACH DATABASE` of SQLite: not supported by Doctrine.

## Revisit when

- The first context moves to DynamoDB in production: its Doctrine repositories get a DynamoDB counterpart, and the local SQLite stays for development.
