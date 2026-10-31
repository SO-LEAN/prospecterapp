# 0089. A Contract declares the service it offers, answering questions on sets; the anticorruption layers depend on it by its type

- Date: 2026-10-31
- Status: Accepted
- Supersedes: for how an anticorruption layer reaches a published query, and for the published queries themselves, [0045](0045-published-language-between-contexts.md)
- Implemented by: `packages/core/src/*/Contract/Service/{ConsultMembers,WhichAreContacts,WhichAreProspected}/`, their handlers in `packages/core/src/*/Application/`, the anticorruption layers of `apps/api/src/*/AntiCorruption` and their doubles in `packages/core/tests/Double`, `ProspectedOrganizations::prospectedAmong()`, `ExchangeReader`, `apps/api/config/services.yaml`, `packages/core/features/langage-publie.feature`

## Context

A Contract publishes the data of a question between contexts (ADR 0045, 0047): the query (`ConsultMember`), its output port, its answer (`MemberResponse`) and the presenter keeping it (`KeptMember`). It does not publish the operation itself. An anticorruption layer receives a `RefusalTranslatingHandler $consultMember`, called `__invoke(object, ?object)`, which the container finds by the name of the parameter: the analysis cannot check that it answers `ConsultMember`, the layer knows that a handler wrapped in a decorator stands behind, and the refusals it catches (`UseCaseRefused`) are written nowhere in the Contract; for the questions answered by yes or no, each layer turns the refusal of an unknown person or organization into `false` itself.

The questions are asked one element at a time, and some clients ask them in a loop: `CollectContact` asks `IsProspectedBy` once per organization of the contact, the `ExchangeReader` of the API `ConsultMember` once per member who noted, corrected or made conform an exchange. In process, a loop costs nothing; called over HTTP, the day a context is deployed apart (the revisit of ADR 0045), it costs a round trip per element. Michel wants the contract of the service as explicit as its data, and fit for a call over HTTP.

## Decision

- **A Contract declares the service it offers**, one interface per published query, beside its data (`<Context>/Contract/Service/<Query>/`), named after its intent and its role (ADR 0087): `ConsultMembersService`, `WhichAreContactsService`, `WhichAreProspectedService`, the service of the open host service; in `Contract/Service`, the suffix names no domain service (ADR 0086). Its method takes the query and the output port and presents the answer (ADR 0085). A Contract still imports nothing (`ContractTest`).
- **A published query asks about a set**, and answers with what exists among it: `ConsultMembers` (the members found, by id), `WhichAreContacts` (the persons, among those asked, who are contacts of the account), `WhichAreProspected` (the organizations, among those asked, the account prospects). One shape per question: a single element is a set of one.
- **A published query refuses nothing**: what is unknown is absent from its answer, and the client decides what an absence means (`prospectorFrom()` refuses an unknown prospector, `CollectContact` an organization its account does not prospect). The Contract declares no exception.
- **The handler of a published query implements the interface of its Contract**: it reads the set in its repository and presents it, without error handling (ADR 0035), nothing to translate.
- **An anticorruption layer depends on the interface**, injected by its type; its double in the scenarios does the same, `tests/Acceptance/Application` giving it the handler. A port of the asking context stays in its own words and asks about a set only where a use case asks about several elements (`ProspectedOrganizations` for `CollectContact`); the others ask about a set of one (`Prospectors::prospectorFrom()`).

## Consequences

- The analysis checks the question an anticorruption layer asks and the answer it reads; nothing is wired by the name of a parameter any more.
- A use case or a reader asking about several elements calls the other context once; over HTTP, one round trip per question.
- A context deployed apart gives its Contract a client calling it over HTTP, implementing the same interface: the anticorruption layers do not change.
- The published language changes: the three queries, their answers, their scenarios (`langage-publie.feature`) and the anticorruption layers, in one change of contract (ADR 0045).
- The difference between an element unknown and an element that does not answer the question is lost to the client: none uses it.

## Alternatives considered

- Keeping the questions on one element, the Contract declaring their refusals (`MemberNotFound`) and an implementation apart translating those of the domain, the handlers staying without error handling: Michel preferred questions on sets, a call over HTTP asking once for several elements, and refusing nothing.

## Revisit when

- A context is called over HTTP: its client implements the interfaces of its Contract.
- A client needs to tell an element unknown from an element that does not answer the question.
