# Testing the core

Two loops, as in *Growing Object-Oriented Software*:

- **Acceptance (outer loop)**: a Gherkin scenario, in French, states a business rule at the entrance of the hexagon. It is written first and stays red until the rule works. It knows requests, responses and the state of the outside world, never how a use case does its job, so refactoring does not touch it.
- **Unit (inner loop)**: PHPUnit for what a scenario cannot reach or would express clumsily (the facade) and for pure logic rich in edge cases (`tests/Domain`: `Email`, identities, and `OrganizationIntentsTest`: every intention is refused exactly when the organization does not offer it, i.e. when its API link is missing). A unit test checks a behaviour, never a structure (no getter and setter tests).

## Nothing inside the hexagon is doubled

Aggregates, value objects, use cases and the services of `src/Application/Service` are the real classes. Only the ports, the interfaces towards the outside, get test implementations, in `tests/Double`. They are working implementations, not mocks; no mocking library is used.

| Port | Double | Behaves like |
|---|---|---|
| `OrganizationRepository`, `UserRepository` | `InMemoryOrganizationRepository`, `InMemoryUserRepository` over `InMemoryDatabase` | the Doctrine repositories: time ordered UUID identities (`TimeOrderedUuids`), email unique among organizations and users, copies stored and returned (a change not saved is lost), search and pagination newest first |
| `Storage` | `InMemoryStorage` | stores by URL `https://storage.test/<file name>` and deletes the uploaded file, like the real storage moves it |
| `GeoLocation` | `KnownPlacesGeoLocation` | finds an address that mentions a place declared by the scenario |
| presenters | `LastResponsePresenter` | keeps the presented response |

## Layout

```
features/*.feature              the business rules, French Gherkin (# language: fr)
tests/Acceptance/Application    the hexagon wired to the doubles; the only place that calls execute()
tests/Acceptance/Context/       step definitions (ScenarioContext shares the state, SharesTheScenario gathers it)
tests/Acceptance/Vocabulary     French labels of the tables, how they read entities and value objects
tests/Acceptance/Expect         assertions (PHPUnit's need its runner to describe a failure)
tests/Double/                   the ports' test implementations
tests/Domain/                   PHPUnit: value objects rich in edge cases
tests/UseCase/                  PHPUnit: roles and the facade
```

The scenarios do not use `UseCasesFacade`: when the use cases are sent as commands to a bus, only `Application` changes.

## Adding or changing a rule

1. Write the scenario in the feature of the intent (one feature per use case, named after it in French: `demenager.feature` for `Relocate`), with the words already used (`Vocabulary` lists the table labels). Run `make test-core`: it must fail, and for the expected reason.
2. Missing step: Behat prints a snippet; add it to the context of the domain (`OrganizationContext`, `AccountContext`) or to `ScenarioContext` for shared ones. Steps arrange data through the aggregates and repositories (and save what they change), act through `Application`, assert on responses or on the stored state.
3. A new port gets a working double in `tests/Double`, behaving like its real adapter.
4. Make it pass in `src`, then refactor with the scenarios green.
5. `make test-core && make lint`, then `make test` (the API uses the core).

Check that a scenario really protects its rule: break the rule in `src`, the scenario must fail, then restore it.
