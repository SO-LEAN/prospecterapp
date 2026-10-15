# 0045. Each context publishes the questions the others ask; their anticorruption layers see nothing else

- Date: 2026-10-16
- Status: Accepted, location superseded by [0047](0047-a-contract-per-context.md)
- Implemented by: `packages/core/src/*/Application/Published`, `apps/api/src/Prospecting/{AccountsProspectorService,PersonalDataKnownContacts}.php`, `apps/api/src/PersonalData/{AccountsReaderService,ProspectingOrganizations}.php`, `packages/core/features/langage-publie.feature`, the `BoundedContextsTest` of the core and of the API

## Context

A context reaches another one through a port of its own words, implemented in the API by an anticorruption adapter (ADR 0031, 0042). The four adapters read the model of the other context: `MemberRepository` and `Member` of Accounts, `PersonRepository`, `Person` and `Purpose` of PersonalData, `OrganizationRepository` and `Organization` of Prospecting; the rule "a person is a contact of the account" was written in an adapter. The contract between the contexts was their internal model, and `BoundedContextsTest` listed the adapters as exceptions: nothing protected it. Later the contexts may be called over HTTP; for now they run in the same process.

## Decision

- Open host service: each context publishes, in `Application/Published/<Query>`, the queries the other contexts ask: `ConsultMember` (Accounts), `IsAContactOf` (PersonalData), `IsProspectedBy` (Prospecting). Their answers are the published language: plain data carrying only what the others need (`MemberResponse`, a `bool`), changing with them, never with the model behind. They check no caller: the use case that asks has identified its member already. They are not routes of the API.
- Anticorruption layer: the adapter of a port calls the published query of the other context, wrapped in `RefusalTranslatingHandler` and injected by the name of its intent (`$consultMember`), and translates it into its own words; a refusal becomes `UnknownProspector`, `UnknownReader`, or `false` for a question about something unknown. Its double in the core is the same code.
- The ports that obtain an actor are named after Vaughn Vernon (*Implementing Domain-Driven Design*): `ProspectorService::prospectorFrom()`, `ReaderService::readerFrom()`. `KnownContacts` and `ProspectedOrganizations` stay questions.
- The rules leave the adapters for the aggregates: `Person::isAContactOf()`.
- `BoundedContextsTest` (core and API) allows a context nothing of another one but its `Application\Published`, and the doubles of the ports likewise; `langage-publie.feature` fixes what the published queries answer.

## Consequences

- A context changes its model without breaking the others; changing a published query is a visible change of contract, covered by its scenario.
- The call stays in process; calling a context over HTTP changes its adapters only.
- One published query, its handler and its scenario for each new question between contexts.

## Alternatives considered

- Speaking the same language (shared kernel or conformist): the model of one context would become the contract of the other.
- Keeping the adapters on the model of the other context, as before: the contract was not protected.
- Reusing `ConsultMyAccount` for the members: its answer serves the screen of the account and changes with it.

## Revisit when

- A context is deployed apart (the adapters call it over HTTP or gRPC), or a published query needs its caller checked.
