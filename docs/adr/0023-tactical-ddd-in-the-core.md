# 0023. Tactical DDD in the core: aggregates, value objects, repositories

- Date: 2026-10-15
- Status: Accepted
- Supersedes: [0018](0018-readonly-core-model.md)
- Implemented by: `packages/core/src/Domain`, `packages/core/src/Application`, `apps/api/src/Repository`

## Context

After 0018 the core was typed and its value objects readonly, but its entities stayed anemic: getters and setters, rules spread in use cases, relations held as objects, integer ids given by the database, a `Person` hierarchy shared by organizations, prospects and users, and names carrying encodings (`*Impl`, `Gateway`). The user wanted the tactical patterns of DDD now, the strategic ones later, in the same iteration as the use cases by intent (0025).

## Decision

- `Domain/{Shared,Organization,User}`: two aggregates, `Organization` and `User`, built by named constructors (`Organization::usingProspecter`, `Organization::prospectedBy`, `User::register`) and changed only through intention methods (`reviseLegalIdentity`, `relocateTo`, `removeLogo`...). They protect their invariants and throw a `BusinessRuleViolation` naming the faulty field.
- Value objects: `OrganizationId`, `UserId` (0024), `Email`, `ContactDetails`, `LegalIdentity`, `PersonName`, `Address`, `GeoPoint`, `File`, `Page`, the `Role` enum.
- Aggregates reference each other by identity (`ownerId`, `holdingId`, `organizationId`); `Person` and `Prospect` are gone; the count of active organizations is a query.
- Repositories in the style of a collection, one per aggregate (`OrganizationRepository`, `UserRepository`: `nextIdentity`, `get`, `save` and the queries the use cases need). A unique email, shared by users and organizations, is enforced by `save()`.
- `Application/`: the use cases, their ports (`Storage`, `GeoLocation`) and services (`FileStore`, `GeoLocalizer`, `ProspectedOrganizations`). Each use case loads one aggregate, calls one intention, saves it: the `Transaction` port is gone, and so is `UserNotifier` (flash messages of the former Twig pages).
- Names say the role, implementations the technology: `OrganizationRepository`, `DoctrineOrganizationRepository`, `InMemoryOrganizationRepository`; no `Impl`, no `Interface` suffix.
- Persistence keeps the flat columns of 0018 (Doctrine cannot map a nullable embeddable); the Doctrine mapping lives in `apps/api/config/doctrine/<Aggregate>/`.

## Consequences

- The rules are in the aggregates, where a scenario reaches them through the use case; use cases are a few lines each.
- One aggregate per use case: no transaction to coordinate, but a change that would span two aggregates now needs two intentions (the language of a user no longer changes the one of their organization).
- The API and the database were rebuilt (no migration: nothing is deployed, 0016).
- Use cases still check roles and ownership themselves (`OrganizationUseCase`, `AccountUseCase`, `UseCaseConsumer`).

## Alternatives considered

- An `Interface` suffix on ports (`OrganizationRepositoryInterface`): the user chose names by role, the implementations named after their technology.
- Keeping integer ids, wrapped in value objects: rejected for identities given by the domain (0024).
- Keeping relations as objects between aggregates: the user chose references by identity.
- Domain events now: the user postponed them, no consumer needs them yet.
- DDD first, intents in a later iteration: the user chose both in the same iteration, since the intention methods are the aggregates' interface.

## Revisit when

- The aggregate, not the use case, should decide who may run an intention (the user's next step: the signed-in user's id travels in the request and the `UseCaseConsumer` goes).
- Domain events or strategic patterns (bounded contexts) become needed.
