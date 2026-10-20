# Testing the core

Two loops, as in *Growing Object-Oriented Software*:

- **Acceptance (outer loop)**: a Gherkin scenario, in French, states a business rule at the entrance of the hexagon. It is written first and stays red until the rule works. It knows requests, responses and the state of the outside world, never how a use case does its job, so refactoring does not touch it.
- **Unit (inner loop)**: PHPUnit for what a scenario cannot reach or would express clumsily for pure logic rich in edge cases (`tests/Domain`: `Email`, identities, and the intents tests of `Organization`, `Account` and `Person`: every intention is refused exactly when the aggregate does not offer it, i.e. when its API link is missing; `tests/Application`: the translation of the refusals; `tests/Architecture`: the boundaries). A unit test checks a behaviour, never a structure (no getter and setter tests).

## Nothing inside the hexagon is doubled

Aggregates, value objects, handlers, services (`FileStore`, `GeoLocalizer`, `EmailUniqueness`, `OrphanContacts`, `ContactOrganizations`, built once by `Application` and injected into the handlers, ADR 0044) and the decorator `RefusalTranslatingHandler` are the real classes. Only the ports, the interfaces towards the outside, get test implementations, in `tests/Double`. They are working implementations, not mocks; no mocking library is used.

| Port | Double | Behaves like |
|---|---|---|
| `AccountRepository`, `MemberRepository`, `OrganizationRepository` | `InMemoryAccountRepository`, `InMemoryMemberRepository`, `InMemoryOrganizationRepository` over `InMemoryDatabase` | the DynamoDB repositories: copies stored and returned (a change not saved is lost, the recorded events are not stored), the answer to `isEmailUsed()` among the organizations of an account (the rule itself is `EmailUniqueness`), slices newest first after a cursor, found by `searchTerms()` (`NewestFirst`) |
| `ExchangeRepository` (Prospecting) | `InMemoryExchangeRepository` over `InMemoryDatabase` | the DynamoDB repository: the timeline of a contact or of an organization by the moment of the exchanges then their identity, the most recent first, after a cursor made of both (ADR 0074, 0076) |
| `ProspectorService` (Prospecting) | `AccountsProspectorService` | the anticorruption adapter of the API, same code: a prospector is the member the published query `ConsultMember` of Accounts answers |
| `PersonRepository`, `ReaderService` (PersonalData) | `InMemoryPersonRepository`, `AccountsReaderService` | the answer to `isEmailUsed()` among the persons of an account for a purpose, the search newest first; a reader is the member `ConsultMember` answers, with its person and its right to read personal data |
| `KnownContacts` (Prospecting) | `PersonalDataKnownContacts` | the anticorruption adapter of the API, same code: the published query `IsAContactOf` of PersonalData |
| `ProspectedOrganizations` (PersonalData) | `ProspectingOrganizations` | the anticorruption adapter of the API, same code: the published query `IsProspectedBy` of Prospecting |
| `Events` | `SynchronousEvents` | the event bus of the API: each event delivered at once to the real event handlers `Application` subscribed |
| `Storage` | `InMemoryStorage` | stores by URL `https://storage.test/<file name>` and deletes the uploaded file, like the real storage moves it |
| `GeoLocation` | `KnownPlacesGeoLocation` | finds an address that mentions a place declared by the scenario |

## Layout

```
features/*.feature              the business rules, French Gherkin (# language: fr)
tests/Acceptance/Application    the hexagon wired to the doubles; the only place that calls a handler
tests/Acceptance/Context/       step definitions (ScenarioContext shares the state, SharesTheScenario gathers it)
tests/Acceptance/Vocabulary     French labels of the tables, how they read entities and value objects
tests/Acceptance/Expect         assertions (PHPUnit's need its runner to describe a failure)
tests/Double/                   the ports' test implementations
tests/Domain/                   PHPUnit: value objects rich in edge cases, intents refused exactly when not offered
tests/Application/              PHPUnit: the translation of the refusals of the domain (UseCaseRefused, RefusalTranslatingHandler)
tests/Architecture/             PHPUnit: no bounded context uses another one but its Contract, a Contract imports nothing,
                                no domain class in a request or a response, every handler a CommandHandler, a
                                QueryHandler or an EventHandler, every event handler subscribed in the scenarios
```

The scenarios do not know the command bus of the API: `Application::dispatch()` sends a command to its handler, and a query is called directly, both through `RefusalTranslatingHandler` like the API. Every event handler of the core, the translators publishing the integration events included (ADR 0046), reacts in the scenarios as in the API (`Application::eventHandlers()`; `tests/Architecture/EventHandlersSubscribedTest` fails when one is missing): a scenario checks the side effects of a gesture in the other contexts, never the events themselves; the intents tests check that an accepted intention records its event. The published queries of the contexts (ADR 0045, `Contract/Service`) are asked through `Application::askPublished()`, as the anticorruption layers ask them, in `features/langage-publie.feature`. `Application` plays the client: it gives the identities of what is created (`newIdentity()`, time ordered UUIDs from `TimeOrderedUuids`) and the moment of the commands (`now()`, set by `nous sommes le "2026-10-20 14:00"`); a refusal is the `UseCaseRefused` of the last call (`refusal()`).

## Adding or changing a rule

1. Write the scenario in the feature of the intent (one feature per use case, named after it in French: `demenager.feature` for `Relocate`), with the words already used (`Vocabulary` lists the table labels). Run `make test-core`: it must fail, and for the expected reason.
2. Missing step: Behat prints a snippet; add it to the context of the domain (`OrganizationContext`, `AccountContext`, `PersonalDataContext`) or to `ScenarioContext` for shared ones. Steps arrange data through the aggregates and repositories (and save what they change), act through `Application`, assert on responses or on the stored state.
3. A new port gets a working double in `tests/Double`, behaving like its real adapter.
4. Make it pass in `src`, then refactor with the scenarios green.
5. `make test-core && make lint`, then `make test` (the API uses the core).

Check that a scenario really protects its rule: break the rule in `src`, the scenario must fail, then restore it.
