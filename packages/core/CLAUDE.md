# prospecter/core

The hexagon of the application: the domain (aggregates, value objects, repository interfaces) and the application (use cases by business intent, their ports and services). Internal Composer package of the monorepo, not published (see `docs/monorepo.md` at the repository root). Tests: `docs/core-tests.md`. Decisions: ADR 0023 (tactical DDD), 0024 (UUID identities), 0025 (one intent per use case).

## Commands

Run from the repository root (the core has its own `vendor/`, installed by `make vendor`):

- `make test-core`: the Behat scenarios (`features/`, one per intent), then PHPUnit (`tests/Domain`, `tests/UseCase`)
- `make lint` / `make fix` cover the core too (PHPStan level 5, php-cs-fixer with strict types, Rector)
- one command: `docker compose run --rm --no-deps -w /var/www/src/packages/core php vendor/bin/behat features/demenager.feature`

## Layout

- `src/Domain/{Shared,Organization,User}`: the aggregates `Organization` and `User`, their identities (`OrganizationId`, `UserId`), value objects (`Email`, `ContactDetails`, `LegalIdentity`, `PersonName`, `Address`, `GeoPoint`, `File`, `Page`, `Role`), repository interfaces and domain exceptions (`BusinessRuleViolation` names the faulty field).
- `src/Application/{Organization,Account}/<Intent>/`: one package per use case, CQRS (ADR 0029). A command (`Relocate/Relocate`, readonly data) and its handler (`RelocateHandler`, implements `CommandHandler`, `__invoke(Relocate): void`) change the state and return nothing. A query (`ConsultOrganization/ConsultOrganization`) and its handler return a response; only queries return data. What the intents of a resource share stays one level up (`OrganizationResponse`, `OrganizationResponses`).
- `src/Application/Port` (`Storage`, `GeoLocation`), `Service` (`FileStore`, `GeoLocalizer`), `CommandHandler`.

## Rules

- Pure PHP: `composer.json` requires only `php` (dev tools aside). Never add a framework or infrastructure dependency; the outside is reached through ports (`Port/`, repository interfaces) implemented in `apps/api`.
- Names: the interface says the role, the implementation its technology (`OrganizationRepository`, `DoctrineOrganizationRepository`, `InMemoryOrganizationRepository`). No `Impl`, no `Interface` suffix, no CRUD verbs in use cases (`Create`, `Get`, `Update`, `Delete`).
- A command handler is one intent: it loads the acting user (the command carries its id) and one aggregate, calls one intention method with `by: $user`, saves it with `save()`, and returns nothing; a query handler only reads. It changes only the data of its intent, and is reused by another client or a data recovery, never by another use case. No transaction: one aggregate per use case.
- Business rules live in the aggregates: named constructors (`Organization::prospectedBy`, `User::register`), intention methods (`relocateTo`, `removeLogo`), no setters; an invariant is checked before the change. Aggregates reference each other by identity, never by object.
- Identities come from the repository (`nextIdentity()`), UUID v7 in the API. A unique email (users and organizations together) is enforced by `save()`.
- Requests, responses and value objects are `final readonly` with public promoted properties; responses carry value objects. Aggregates hold their value objects as they are (`private ?Address $address`): the domain knows nothing of persistence, the API mapping adapts to it (ADR 0028). A new property of an aggregate or of a value object requires a change of `apps/api/config/doctrine/`.
- Authentication and roles are not the business of the core: the delivery authenticates and checks roles, a request carries the id of the acting user. The aggregate decides the rest (ADR 0026): an organization is managed by its own users and by those of the organization prospecting it, anybody else gets `UnknownOrganization` (not found).
- An aggregate offers its possible intents (`Organization::possibleIntentsOf($user)`, `User::possibleIntents()`), which the API turns into links (ADR 0027); every intention method starts with the same check (`ensurePossible`) and throws `NotPossibleInItsState` for what is not offered. A new intention gets a case in the intent enum, a check, and a line in `OrganizationIntentsTest`.
- Services of `src/Application/Service` are real classes of the hexagon, built by the use cases, never doubled.
- The API sends commands on a synchronous Symfony Messenger bus (every `CommandHandler` is tagged `messenger.message_handler`) and calls query handlers directly; it builds commands with named arguments: renaming a property breaks it (PHPStan and the API tests catch it), so change the core and the API in the same commit.
- The core knows no bus: `tests/Acceptance/Application` sends a command to its handler (found by the type of `__invoke()`, as Messenger does) and is the only place that knows how a handler is called.
- Clean code (`docs/clean-code.md`): names instead of explanatory comments, docblocks only for generics and array shapes.

## History

Imported from `SO-LEAN/cleanprospecter` branch `develop` (2021, first-parent history replayed). That repository still has `claude/refactor-ddd-architecture-UJMig`, an unmerged DDD refactoring.
