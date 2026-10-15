# 0028. Aggregates keep their value objects; the Doctrine mapping adapts to them

- Date: 2026-10-15
- Status: Accepted
- Supersedes: the persistence point of [0023](0023-tactical-ddd-in-the-core.md) (flat columns inherited from [0018](0018-readonly-core-model.md))
- Implemented by: `packages/core/src/Domain`, `apps/api/src/Doctrine/Type`, `apps/api/config/doctrine`

## Context

Doctrine cannot make an embeddable null. To map an optional address or logo, the aggregates stored the fields of their value objects in plain properties (`addressCity`, `logoUrl`...), rebuilt them in their accessors and copied them back in `assign*` methods; a comment of the aggregate even named Doctrine. The user found it unclean and risky (a field forgotten in a copy goes unnoticed): the domain was bending to the ORM.

## Decision

- Aggregates hold their value objects as they are (`private ?Address $address`, `private ContactDetails $contactDetails`, `private readonly OrganizationId $id`); no `assign*` method, no persistence word in the core.
- The API mapping adapts: value objects always present are embeddables without column prefix (`LegalIdentity`, `ContactDetails`, `PersonName`), the others are custom Doctrine types (`OrganizationId`, `UserId` as `guid`, `Email` as a string, `Address`, `GeoPoint`, `File` and the roles as JSON columns).
- The database was recreated: nothing is deployed, no migration (0016).

## Consequences

- An aggregate reads as the business describes it; a value object is copied whole or not at all.
- Address, position and images are JSON: they cannot be queried in SQL (nothing needs it today); names and emails stay plain columns for the search and the unique email.
- An aggregate must never be a Doctrine lazy proxy: initializing one would overwrite its readonly identity with an equal but new value object, which Doctrine refuses. The code creates none (no association, no `getReference()`), the tests load fixture users again.

## Alternatives considered

- Keeping the flat fields of 0018 and 0023: rejected by the user, a persistence limit leaking into the domain.

## Revisit when

A search or a report needs to query the address or another JSON value object, or Doctrine supports nullable embeddables.
