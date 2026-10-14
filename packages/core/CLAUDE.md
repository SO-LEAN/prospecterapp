# prospecter/core

The hexagon of the application: entities, use cases and gateway interfaces (ports). Internal Composer package of the monorepo, not published (see `docs/monorepo.md` at the repository root).

## Rules

- Pure PHP. `composer.json` requires only `php`: never add a framework or infrastructure dependency (Symfony, Doctrine, HTTP clients...). Infrastructure is reached through the interfaces in `src/Gateway` and implemented in `apps/api/src/Gateway`.
- Entities are mapped by the API (`apps/api/config/doctrine/*.orm.xml`): adding, renaming or retyping an entity property requires updating that mapping.
- A use case is `UseCase/<Name>/<Name>Impl.php` (extends `AbstractUseCase`, `execute(<Name>Request, <Name>Presenter, ?UseCaseConsumer)`, roles in `canBeExecutedBy()`), with its `<Name>Request`, `<Name>Response` and `<Name>Presenter`. The API registers every `*Impl` automatically and calls it as `$facade-><name>()` (lcfirst); its controllers build the requests with named arguments, so renaming a request parameter breaks them (PHPStan and the API tests catch it).
- Only the API consumes it: change its API and the API adapters in the same commit.
- The current use cases are CRUD-shaped and are meant to be renamed to business intents; the API URLs are resource-oriented on purpose so that only the controllers follow such a rename.
- Store uploaded files with `StoreFileTrait::storeFile()`: `Storage::add()` may move the file, so its metadata must be read before (tests simulate this with `UseCaseTestCase::mockUploadedFile()`).
- Same code conventions as the rest of the repository (root `CLAUDE.md`).

## Tests

`tests`, namespace `Tests\Core\`, run from the root with `make test` (testsuite `core`). Builders in `tests/Builder` and helper functions in `tests/Base/functions.php` (`anOrganization()`, `aRequest()`...). Base classes `TestCase`, `EntityTestCase`, `UseCaseTestCase`; Prophecy through `prophesy()`.

## History

Imported from `SO-LEAN/cleanprospecter` branch `develop` (2021, first-parent history replayed). That repository still has `claude/refactor-ddd-architecture-UJMig`, an unmerged DDD/hexagonal refactoring (value objects with named constructors, authentication use cases removed from the hexagon) that the API does not support yet.
