# 0048. DynamoDB, one table per bounded context, emulated locally by MiniStack

- Date: 2026-10-16
- Status: Accepted
- Supersedes: SQLite and the Doctrine mapping of the API ([0016](0016-sqlite-without-migrations.md), the mapping of [0028](0028-aggregates-keep-their-value-objects.md)), the database of each context ([0038](0038-one-database-per-bounded-context.md), now a table), the unique index as safety net ([0039](0039-set-rules-in-domain-services-indexes-as-safety-net.md), now a conditional reservation)
- Implemented by: `apps/api/src/Shared/Persistence/{Table,Tables,Hydrator,Documents,ConditionFailed}.php`, `apps/api/src/*/Persistence/Dynamo*Repository.php`, `apps/api/src/Demo/DemoData.php`, the `ministack` service of `docker-compose.yml`, `packages/core/tests/Architecture/DomainClassesAreFinalTest.php`

## Context

The application is to run serverless on AWS (Lambda with Bref, API Gateway, S3, Cognito, Cloudflare in front), whose database is DynamoDB; SQLite and Doctrine were a local choice to revisit before production (0016). Only the local development is in scope for now; the infrastructure (Terraform) comes later. LocalStack, the usual emulator, no longer has a free edition for commercial use and keeps DynamoDB Streams, Cognito and API Gateway v2 for paid plans.

## Decision

- The API keeps its aggregates in DynamoDB through the official AWS SDK for PHP. Doctrine leaves the API (it stays in the auth app until Cognito replaces it).
- Each bounded context has one table, `<prefix>-<context>`, in single-table design: `PK`/`SK`, two secondary indexes `GSI1` and `GSI2`, each repository documenting its items. A `Table` is injected by name in the persistence of its context only.
- An aggregate is stored as a plain document and restored without its constructor (`Hydrator`): the domain keeps its named constructors, private properties and value objects (0028), nothing of the persistence in the core. The aggregates become `final` again, Doctrine having needed proxies extending them; a test keeps every class of a domain final unless the domain extends it.
- A rule on a set of aggregates keeps its domain service (0039); its safety net is a reservation item (`EMAIL#<account>#<email>`) written in the same transaction as the aggregate under a condition: a second holder cancels the transaction into the refusal of the domain.
- Locally, MiniStack (MIT, one container, port 4566, state kept in a volume) emulates DynamoDB, and later S3 and Cognito. The tables are created by `app:tables:recreate`, with the data of the demonstration written through the domain. A test failing on a limit of MiniStack is skipped with its reason; a dev instance on AWS will be the safety net.

## Consequences

- No join, no count, no text search: the lists go by cursor (0049), the search by a projection (0050), the links between aggregates are items written with them.
- A new property of an aggregate or a value object needs a change of its document mapping, as it needed one of the Doctrine mapping.
- The tests of the API need MiniStack running (`make test-api` starts it).
- MiniStack is young: what it emulates may differ from AWS.

## Alternatives considered

- LocalStack: its free plan is non-commercial and lacks Cognito and API Gateway v2.
- The official DynamoDB Local image alone: faithful for DynamoDB, but S3 and Cognito would need other emulators.
- `async-aws/dynamo-db`, lighter: the official SDK brings the marshaler of the documents and covers S3 and Cognito with the same client.

## Revisit when

- MiniStack lags behind a feature the application needs, or the dev instance on AWS shows a divergence.
