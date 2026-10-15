# prospecter/core

The hexagon of the application: the domain (aggregates, value objects, repository interfaces) and the application (use cases by business intent, their ports and services). Internal Composer package of the monorepo, not published (see `docs/monorepo.md` at the repository root). Tests: `docs/core-tests.md`. Decisions: ADR 0023 (tactical DDD), 0024 (UUID identities), 0025 (one intent per use case).

## Commands

Run from the repository root (the core has its own `vendor/`, installed by `make vendor`):

- `make test-core`: the Behat scenarios (`features/`, one per intent), then PHPUnit (`tests/Domain`, `tests/UseCase`)
- `make lint` / `make fix` cover the core too (PHPStan level 5, php-cs-fixer with strict types, Rector)
- one command: `docker compose run --rm --no-deps -w /var/www/src/packages/core php vendor/bin/behat features/demenager.feature`

## Layout

- `src/Domain/{Shared,Organization,User}`: the aggregates `Organization` and `User`, their identities (`OrganizationId`, `UserId`), value objects (`Email`, `ContactDetails`, `LegalIdentity`, `PersonName`, `Address`, `GeoPoint`, `File`, `Page`, `Role`), repository interfaces and domain exceptions (`BusinessRuleViolation` names the faulty field).
- `src/Application/{Organization,Account}`: one use case per intent (`Relocate`) with its `RelocateRequest`; responses and presenters shared by the intents of a resource (`OrganizationResponse`, `OrganizationPresenter`). Bases `OrganizationUseCase` (prospector role, ownership) and `AccountUseCase` (user role, the signed-in user).
- `src/Application/Port` (`Storage`, `GeoLocation`), `Service` (`FileStore`, `GeoLocalizer`, `ProspectedOrganizations`), `Exception` (`NotFoundException`, `UseCaseException`...), and `UseCase`, `UseCaseConsumer`, `UseCasesFacade`.

## Rules

- Pure PHP: `composer.json` requires only `php` (dev tools aside). Never add a framework or infrastructure dependency; the outside is reached through ports (`Port/`, repository interfaces) implemented in `apps/api`.
- Names: the interface says the role, the implementation its technology (`OrganizationRepository`, `DoctrineOrganizationRepository`, `InMemoryOrganizationRepository`). No `Impl`, no `Interface` suffix, no CRUD verbs in use cases (`Create`, `Get`, `Update`, `Delete`).
- A use case is one intent: it loads one aggregate, calls one intention method on it, saves it with `save()` and presents it. It changes only the data of its intent, and is reused by another client or a data recovery, never by another use case. No transaction: one aggregate per use case.
- Business rules live in the aggregates: named constructors (`Organization::prospectedBy`, `User::register`), intention methods (`relocateTo`, `removeLogo`), no setters; an invariant is checked before the change. Aggregates reference each other by identity, never by object.
- Identities come from the repository (`nextIdentity()`), UUID v7 in the API. A unique email (users and organizations together) is enforced by `save()`.
- Requests, responses and value objects are `final readonly` with public promoted properties; responses carry value objects. Aggregates keep the fields of their value objects in plain properties (`addressCity`, `logoUrl`...) and build them in their accessors, because Doctrine cannot map a nullable embeddable: a property change requires a change of `apps/api/config/doctrine/<Aggregate>/*.orm.xml`.
- Authentication is not the business of the core: a use case receives an already authenticated `UseCaseConsumer` (roles in `canBeExecutedBy()`, ownership with `ProspectedOrganizations`). There is no password here.
- Services of `src/Application/Service` are real classes of the hexagon, built by the use cases, never doubled.
- The API calls the use cases through `UseCasesFacade` (`$facade-><name>()`, which checks the roles) and builds requests with named arguments: renaming a request property breaks it (PHPStan and the API tests catch it), so change the core and the API in the same commit.
- The facade is meant to give way to a command bus (ADR 0020): scenarios never depend on it (`tests/Acceptance/Application` is the only place that knows how a use case is called).
- Clean code (`docs/clean-code.md`): names instead of explanatory comments, docblocks only for generics and array shapes.

## History

Imported from `SO-LEAN/cleanprospecter` branch `develop` (2021, first-parent history replayed). That repository still has `claude/refactor-ddd-architecture-UJMig`, an unmerged DDD refactoring.
