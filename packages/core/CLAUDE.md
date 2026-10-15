# prospecter/core

The hexagon of the application: entities, value objects, use cases and the ports (interfaces) they reach the outside through. Internal Composer package of the monorepo, not published (see `docs/monorepo.md` at the repository root). Tests: `docs/core-tests.md`.

## Commands

Run from the repository root (the core has its own `vendor/`, installed by `make vendor`):

- `make test-core`: the Behat scenarios (`features/`), then PHPUnit (`tests/UseCase`)
- `make lint` / `make fix` cover the core too (PHPStan level 5, php-cs-fixer with strict types, Rector)
- one command: `docker compose run --rm --no-deps -w /var/www/src/packages/core php vendor/bin/behat features/mon-compte.feature`

## Rules

- Pure PHP: `composer.json` requires only `php` (dev tools aside). Never add a framework or infrastructure dependency; the outside is reached through `src/Gateway` and implemented in `apps/api`.
- Authentication is not the business of the core: a use case receives an already authenticated `UseCaseConsumer` and only decides what it may do (roles in `canBeExecutedBy()`, ownership with `Service\ProspectedOrganizations`). There is no password here.
- A use case is `UseCase/<Name>/<Name>Impl.php` (extends `AbstractUseCase`) with its interface, `<Name>Request`, `<Name>Response` and `<Name>Presenter`. Requests and responses are `final readonly` classes with public promoted properties; responses carry value objects (`Address`, `GeoPoint`, `File`).
- Entities keep their state private (typed properties, getters and setters, Doctrine maps them from `apps/api/config/doctrine/*.orm.xml`: a property change requires a mapping change). Value objects are `final readonly`; an entity stores their fields in plain properties (`addressCity`, `logoUrl`...) and builds them in its getters, because Doctrine cannot map a nullable embeddable.
- A use case saves what it changes through its gateway (`update()`): the in-memory database of the scenarios keeps copies, like a real one, so a forgotten save fails a scenario.
- Collaborators shared by use cases live in `src/Service` (`FileStore`, `GeoLocalizer`, `ProspectedOrganizations`): real classes of the hexagon, built by the use cases, never doubled.
- The API calls the use cases through `UseCasesFacade` (`$facade-><name>()`, which checks the roles) and builds requests with named arguments: renaming a request property breaks it (PHPStan and the API tests catch it), so change the core and the API in the same commit.
- The use cases are CRUD-shaped and meant to be renamed to business intents, and the facade is meant to give way to a command bus: scenarios never depend on either (`tests/Acceptance/Application` is the only place that knows how a use case is called).
- Clean code (root `CLAUDE.md`): names instead of explanatory comments, docblocks only for generics and array shapes.

## History

Imported from `SO-LEAN/cleanprospecter` branch `develop` (2021, first-parent history replayed). That repository still has `claude/refactor-ddd-architecture-UJMig`, an unmerged DDD refactoring.
