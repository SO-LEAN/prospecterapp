# Testing the core

Two loops, as in *Growing Object-Oriented Software*:

- **Acceptance (outer loop)**: a Gherkin scenario, in French, states a business rule at the entrance of the hexagon. It is written first and stays red until the rule works. It knows requests, responses and the state of the outside world, never how a use case does its job, so refactoring does not touch it. The access to a resource of Prospecting (unknown for another account, refused without the right to manage personal data) is no rule of the core: the voters of the API check it, and its tests prove it (ADR 0081).
- **Unit (inner loop)**: PHPUnit for what a scenario cannot reach or would express clumsily for pure logic rich in edge cases (`tests/Domain`: `Email`, identities, and the intents tests of `Organization`, `Account` and `Person`: every intention is refused exactly when the aggregate does not offer it, i.e. when its API link is missing; `tests/Application`: the translation of the refusals; `tests/Architecture`: the boundaries). A unit test checks a behaviour, never a structure (no getter and setter tests).

## Nothing inside the hexagon is doubled

Aggregates, value objects, handlers, services (`FileStore`, `GeoLocalizer`, `EmailUniqueness`, `OrphanContacts`, `ContactOrganizations`, `AttachedFiles`, built once by `Application` and injected into the handlers, ADR 0044) and the decorator `RefusalTranslatingHandler` are the real classes. Only the ports, the interfaces towards the outside, get test implementations, in `tests/Double`. They are working implementations, not mocks; no mocking library is used.

| Port | Double | Behaves like |
|---|---|---|
| `AccountRepository`, `MemberRepository`, `OrganizationRepository` | `InMemoryAccountRepository`, `InMemoryMemberRepository`, `InMemoryOrganizationRepository` over `InMemoryDatabase` | the DynamoDB repositories: copies stored and returned (a change not saved is lost, the recorded events are not stored), the answer to `isEmailUsed()` among the organizations of an account (the rule itself is `EmailUniqueness`) |
| `ExchangeRepository` (Prospecting) | `InMemoryExchangeRepository` over `InMemoryDatabase` | the DynamoDB repository: the exchanges kept for an organization, those with a contact |
| the retrievers of the read models (`Application/Port`, ADR 0088) | `InMemoryFoundOrganizationRetriever`, `InMemoryOrganizationSheetRetriever`, `InMemoryExchangeDetailRetriever`, `InMemoryTimelineRetriever`, `InMemoryMyAccountRetriever`, `InMemoryMemberRetriever`, `InMemoryPersonCardRetriever`, `InMemoryContactPageRetriever` over `InMemoryDatabase` | the DynamoDB retrievers: what the repositories saved, read without its getters (`StateOf`, as `Hydrator` does in the API), each asking the policies of the domain; slices newest first after a cursor, found by `searchKeys()` (`NewestFirst`); a timeline by the moment of the exchanges then their identity, the most recent first, after a cursor made of both (ADR 0074, 0076), those of an organization to make conform counted (ADR 0079) |
| `Prospectors` (Prospecting) | `AccountsProspectors` | the anticorruption adapter of the API, same code: a prospector is the member the service `ConsultMembersService` of Accounts answers, asked about a set of one (ADR 0089) |
| `PersonRepository`, `Readers` (PersonalData) | `InMemoryPersonRepository`, `AccountsReaders` | the answer to `isEmailUsed()` among the persons of an account for a purpose; a reader is the member `ConsultMembersService` answers, with its person and its right to read personal data |
| `KnownContacts` (Prospecting) | `PersonalDataKnownContacts` | the anticorruption adapter of the API, same code: the service `WhichAreContactsService` of PersonalData, asked about a set of one |
| `ProspectedOrganizations` (PersonalData) | `ProspectingOrganizations` | the anticorruption adapter of the API, same code: the service `WhichAreProspectedService` of Prospecting, asked once for all the organizations of a contact |
| `Events` | `SynchronousEvents` | the event bus of the API: each event delivered at once to the real event handlers `Application` subscribed |
| `Storage` | `InMemoryStorage` | stores by URL `https://storage.test/<file name>` and deletes the uploaded file, like the real storage moves it |
| `PrivateStorage` | `InMemoryPrivateStorage` | the private files of the storage: the client sends an upload (`send()`), kept once, read by a link while it is kept (ADR 0077) |
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

The scenarios do not know the command bus of the API: `Application::dispatch()` sends a command to its handler, and a query is called directly with the presenter of the core keeping its answer (`KeptOrganization`, ADR 0085), both through `RefusalTranslatingHandler` like the API; a scenario reads the response the method of `Application` gives back. Every event handler of the core, the translators publishing the integration events included (ADR 0046), reacts in the scenarios as in the API (`Application::eventHandlers()`; `tests/Architecture/EventHandlersSubscribedTest` fails when one is missing): a scenario checks the side effects of a gesture in the other contexts, never the events themselves; the intents tests check that an accepted intention records its event. The services the contexts publish (ADR 0045, 0089, `Contract/Service`) are asked through `Application::consultMembers()`, `whichAreContacts()` and `whichAreProspected()`, as the anticorruption layers ask them, in `features/langage-publie.feature`. `Application` plays the client: it gives the identities of what is created (`newIdentity()`, time ordered UUIDs from `TimeOrderedUuids`) and the moment of the commands (`now()`, set by `nous sommes le "2026-10-20 14:00"`); a refusal is the `UseCaseRefused` of the last call (`refusal()`).

## Adding or changing a rule

1. Write the scenario in the feature of the intent (one feature per use case, named after it in French: `demenager.feature` for `Relocate`), with the words already used (`Vocabulary` lists the table labels). Run `make test-core`: it must fail, and for the expected reason.
2. Missing step: Behat prints a snippet; add it to the context of the domain (`OrganizationContext`, `AccountContext`, `PersonalDataContext`) or to `ScenarioContext` for shared ones. Steps arrange data through the aggregates and repositories (and save what they change), act through `Application`, assert on responses or on the stored state.
3. A new port gets a working double in `tests/Double`, behaving like its real adapter.
4. Make it pass in `src`, then refactor with the scenarios green.
5. `make test-core && make lint`, then `make test` (the API uses the core).

Check that a scenario really protects its rule: break the rule in `src`, the scenario must fail, then restore it.

## Tests of the API

- `tests/Api`: functional tests through `Base/ApiTestCase`, whose `login()` signs a member in for real on the user pool `prospecter-test` of MiniStack (`AccessTokenTest`, `SignInTest`). The tables of the prefix `prospecter-test` and the user pool are recreated for each test, in MiniStack (`make test-api` starts it), with the data and the members of the demonstration; host `api.test.prospecter.io`; variables in `.env.test`.
- `tests/<Context>`: the adapters on their real table or service (`DynamoPersonRepositoryTest`: the reservation refuses an email taken meanwhile; `OrganizationSearchTest`: found by the beginning of the name or of the email, the keys written again; `GeoplateformeGeoLocationTest`; `SignIn/RsaPublicKeyTest`); `tests/Architecture`: the boundaries (contexts, domain behind the application, one table per context); `tests/Local`: what only the local stack has (the profiler, its CORS). A test failing because of a limit of MiniStack is skipped with its reason; a dev instance on AWS is the safety net.
- Unit tests extend PHPUnit's `TestCase` and use hand-written doubles (`tests/Double`), no mock library.
- PHPUnit 13: attributes only (`#[DataProvider]`, `#[Group]`), static data providers, abstract base classes named `*TestCase`.

## Tests of the storage

`tests/StorageTest`: the storage as the API and the browsers use it, on the bucket `prospecter-test-files` of MiniStack, recreated before each test; `tests/PrivateFilesTest` the private files, on `prospecter-test-private-files`, the test sending and reading as a browser.
