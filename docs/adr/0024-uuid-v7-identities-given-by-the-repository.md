# 0024. Identities are UUID v7 given by the repository

- Date: 2026-10-15
- Status: Accepted
- Implemented by: `packages/core/src/Domain/Shared/Uuid.php`, `DoctrineOrganizationRepository::nextIdentity()`, `DoctrineUserRepository::nextIdentity()`

## Context

Ids were integers given by the database: an aggregate had no identity before being saved, and URLs exposed a guessable sequence. Aggregates now reference each other by identity (0023).

## Decision

- An aggregate receives its identity at construction from `nextIdentity()` of its repository.
- Identities are value objects (`OrganizationId`, `UserId`, extending `Uuid`) validated by a pattern: the core stays pure PHP.
- The API generates UUID v7 (`symfony/uid`), time ordered; the scenarios use a monotonic double (`TimeOrderedUuids`). Columns are `guid`, routes require a UUID, the front refuses any other segment with a 404.
- The database was recreated: nothing is deployed, no migration (0016).

## Consequences

- An aggregate is complete before it is saved; ids no longer leak how many rows exist.
- The newest first order of a search can rely on the identity.
- Ids are longer in URLs and logs.

## Alternatives considered

- Integer ids from the database wrapped in value objects: the user chose identities given by the domain.
- Migrating the existing data: the user ruled it out, the software is not deployed.

## Revisit when

The application is deployed: a later change of identity would then need a migration.
