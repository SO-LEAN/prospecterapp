# so-lean/cleanprospecter

The hexagon of the application: entities, use cases and gateway interfaces (ports). Internal Composer package of the monorepo, not published (see `docs/monorepo.md` at the repository root).

## Rules

- Pure PHP. `composer.json` requires only `php`: never add a framework or infrastructure dependency (Symfony, Doctrine, HTTP clients...). Infrastructure is reached through the interfaces in `src/CleanProspecter/Gateway` and implemented in `webapp/src/Gateway`.
- Entities are mapped by the webapp (`webapp/config/doctrine/*.orm.xml`): adding, renaming or retyping an entity property requires updating that mapping.
- A use case is `UseCase/<Name>/<Name>Impl.php` (extends `AbstractUseCase`, `execute(<Name>Request, <Name>Presenter, ?UseCaseConsumer)`, roles in `canBeExecutedBy()`), with its `<Name>Request`, `<Name>Response` and `<Name>Presenter`. The webapp registers every `*Impl` automatically and calls it as `$facade-><name>()` (lcfirst). Request constructor parameter names must match the webapp form field names.
- Only the webapp consumes it: change its API and the webapp adapters in the same commit.
- Same code conventions as the rest of the repository (root `CLAUDE.md`).

## Tests

`tests/Unit`, namespace `Tests\Unit\Solean\`, run from the root with `make test` (testsuite `cleanprospecter`). Builders in `tests/Unit/CleanProspecter/Builder` and helper functions in `tests/Unit/Base/functions.php` (`anOrganization()`, `aRequest()`...). Base classes `TestCase`, `EntityTestCase`, `UseCaseTestCase`; Prophecy through `prophesy()`.

## History

Imported from `SO-LEAN/cleanprospecter` branch `develop` (2021, first-parent history replayed). That repository still has `claude/refactor-ddd-architecture-UJMig`, an unmerged DDD/hexagonal refactoring (value objects with named constructors, authentication use cases removed from the hexagon) that the webapp does not support yet.

Known bug: `CreateOrganizationImpl` and `UpdateMyAccountInformationImpl` call `getSize()`/`getExtension()` on the uploaded file after `Storage::add()` moved it; compute them before adding.
