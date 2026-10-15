# prospecter/core

The hexagon of the application: the domain (aggregates, value objects, repository interfaces) and the application (use cases by business intent, their ports and services). Internal Composer package of the monorepo, not published (see `docs/monorepo.md` at the repository root). Tests: `docs/core-tests.md`. Decisions: ADR 0023 (tactical DDD), 0024 (UUID identities), 0025 (one intent per use case).

## Commands

Run from the repository root (the core has its own `vendor/`, installed by `make vendor`):

- `make test-core`: the Behat scenarios (`features/`, one per intent), then PHPUnit (`tests/Domain`, `tests/UseCase`)
- `make lint` / `make fix` cover the core too (PHPStan level 5, php-cs-fixer with strict types, Rector)
- one command: `docker compose run --rm --no-deps -w /var/www/src/packages/core php vendor/bin/behat features/demenager.feature`

## Layout

Grouped by bounded context, then by layer (ADR 0030); `tests/Architecture/BoundedContextsTest` fails when a context uses another one.

- `src/Accounts/{Domain,Application}`: an organization using Prospecter (`Account`) and its members (`Member`: account, person id, `Role`; signing in, with a login email, belongs to authentication outside the core); the account intents.
- `src/PersonalData/{Domain,Application}`: the personal data of natural persons (`Person`: account, `Purpose`, `PersonName`, contact details, picture), read in clear or masked (`Masking`) by a `Reader` obtained through the port `Readers`; queries `ConsultPerson` and `ConsultPersons` (batch), commands to change a person (ADR 0031). No other context keeps personal data, only a `PersonId`.
- `src/Prospecting/{Domain,Application}`: the organizations an account prospects (`Organization`, `OrganizationId`, `Address`, `GeoPoint`), the `Prospector` who acts, read through the port `Prospectors` (implemented outside the core from the members of Accounts), the port `GeoLocation`.
- `src/SharedKernel/{Domain,Application}`: what every context shares, kept small: the published identities `AccountId`, `MemberId` and `PersonId`, `Email`, `ContactDetails`, `LegalIdentity`, `File`, `Page`, `Uuid`, the exceptions (`NotFound`, `BusinessRuleViolation` naming the faulty field, `NotPossibleInItsState`, `NotAllowed`), `CommandHandler`, the port `Storage` and `FileStore`.
- `<Context>/Application/<Intent>/`: one package per use case, CQRS (ADR 0029). A command (`Relocate/Relocate`, readonly data) and its handler (`RelocateHandler`, implements `CommandHandler`, `__invoke(Relocate): void`) change the state and return nothing. A query (`ConsultOrganization/ConsultOrganization`) and its handler return a response; only queries return data. What the intents of a context share stays one level up (`OrganizationResponse::of()`, `AccountResponses`).

## Rules

- Pure PHP: `composer.json` requires only `php` (dev tools aside). Never add a framework or infrastructure dependency; the outside is reached through ports (`Port/`, repository interfaces) implemented in `apps/api`.
- Names: the interface says the role, the implementation its technology (`OrganizationRepository`, `DoctrineOrganizationRepository`, `InMemoryOrganizationRepository`). No `Impl`, no `Interface` suffix, no CRUD verbs in use cases (`Create`, `Get`, `Update`, `Delete`).
- A command handler is one intent: it loads the acting member or prospector (the command carries its `MemberId`) and one aggregate, calls one intention method with `by: $user`, saves it with `save()`, and returns nothing; a query handler only reads. It changes only the data of its intent, and is reused by another client or a data recovery, never by another use case. No transaction: one aggregate per use case.
- Business rules live in the aggregates: named constructors (`Organization::prospectedBy`, `Account::open`, `Member::register`), intention methods (`relocateTo`, `removeLogo`), no setters; an invariant is checked before the change. Aggregates reference each other by identity, never by object.
- Identities come from the repository (`nextIdentity()`), UUID v7 in the API. A unique email is enforced by `save()`: among the persons of an account for a purpose (PersonalData), among the organizations of an account (Prospecting).
- Requests, responses and value objects are `final readonly` with public promoted properties; responses carry value objects. Aggregates hold their value objects as they are (`private ?Address $address`): the domain knows nothing of persistence, the API mapping adapts to it (ADR 0028). A new property of an aggregate or of a value object requires a change of `apps/api/config/doctrine/`.
- Authentication and roles are not the business of the core: the delivery authenticates and checks roles, a request carries the id of the acting user. The aggregate decides the rest (ADR 0026): an organization is managed by the prospectors of the account prospecting it, anybody else gets `UnknownOrganization` (not found); an account by its members.
- An aggregate offers its possible intents (`Organization::possibleIntentsOf($prospector)`, `Account::possibleIntentsOf($member)`, `Member::possibleIntents()`), which the API turns into links (ADR 0027); every intention method starts with the same check (`ensurePossible`) and throws `NotPossibleInItsState` for what is not offered. A new intention gets a case in the intent enum, a check, and a line in `OrganizationIntentsTest`, `AccountIntentsTest` or `PersonIntentsTest`.
- Services (`FileStore`, `GeoLocalizer`) are real classes of the hexagon, built by the handlers, never doubled.
- A context never uses another one: it declares a port in its own words (`Prospectors`, `Readers`), implemented outside the core by an anticorruption adapter (`App\\Prospecting\\AccountsProspectors`, `App\\PersonalData\\AccountsReaders`), and doubled in the scenarios (`MembersAsProspectors`, `MembersAsReaders`).
- The API sends commands on a synchronous Symfony Messenger bus (every `CommandHandler` is tagged `messenger.message_handler`) and calls query handlers directly; it builds commands with named arguments: renaming a property breaks it (PHPStan and the API tests catch it), so change the core and the API in the same commit.
- The core knows no bus: `tests/Acceptance/Application` sends a command to its handler (found by the type of `__invoke()`, as Messenger does) and is the only place that knows how a handler is called.
- Clean code (`docs/clean-code.md`): names instead of explanatory comments, docblocks only for generics and array shapes.

## History

Imported from `SO-LEAN/cleanprospecter` branch `develop` (2021, first-parent history replayed). That repository still has `claude/refactor-ddd-architecture-UJMig`, an unmerged DDD refactoring.
