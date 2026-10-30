# 0088. A query reads its read model through a port, in the table of the aggregate; a policy says what is possible

- Date: 2026-10-30
- Status: Proposed
- Supersedes: for who computes what the state offers, [0027](0027-hypermedia-links-from-the-possible-intents.md) and [0081](0081-the-api-checks-the-access-the-core-keeps-the-state.md); specifies [0029](0029-cqrs-with-a-synchronous-messenger-command-bus.md) for what a query reads
- Implemented by: in part, the reading of `SearchProspectedOrganizations` (`packages/core/src/Prospecting/Application/Port/FoundOrganizationRetriever.php`, `FoundOrganizationPage`, `apps/api/src/Prospecting/Persistence/DynamoFoundOrganizationRetriever.php`, `packages/core/tests/Double/InMemoryFoundOrganizationRetriever.php`), of `ConsultOrganizationsOfContacts` (`OrganizationOfContactRetriever`), of `ConsultOrganization` and `RecognizeCallingOrganization` (`OrganizationSheet`, `OrganizationSheetRetriever`, `Domain/Policy/OrganizationPossibilities.php`, `apps/api/tests/Prospecting/DynamoOrganizationSheetRetrieverTest.php`), of `ConsultExchange` (`ExchangeDetail`, `ExchangeDetailRetriever`, `Domain/Policy/{ExchangePossibilities,ExchangeConformity,ExchangeReading}.php`, `apps/api/tests/Prospecting/DynamoExchangeDetailRetrieverTest.php`), of the timelines (`Timeline`, `ExchangeInTimeline`, `TimelineRetriever`, `apps/api/src/Prospecting/Persistence/ExchangeKeys.php`, `apps/api/tests/Prospecting/DynamoTimelineRetrieverTest.php`), of Accounts (`MyAccount`, `MyAccountRetriever`, `MemberRetriever`, `Accounts/Domain/Policy/{AccountPossibilities,PersonalDataRight}.php`, `apps/api/tests/Accounts/DynamoAccountsRetrieversTest.php`), of PersonalData (`PersonCard`, `ContactPage`, `PersonCardRetriever`, `ContactPageRetriever`, `PersonalData/Domain/Policy/{PersonReading,PersonPossibilities}.php`, `apps/api/tests/PersonalData/DynamoPersonalDataRetrieversTest.php`); the getters of the aggregates, still read by the in-memory retrievers, not yet

## Context

The commands and the queries are separated on their buses (ADR 0029), but they share one model: a query restores the aggregate from its repository and projects it into its response (`OrganizationResponse::of(Organization)`). The aggregates expose their state through getters that serve this projection only (`Member::personId()`, `Organization::legalIdentity()`, `address()`...): the persistence reads and restores them by reflection (`Hydrator`, ADR 0028). A list restores every aggregate of its slice, and the repositories carry methods only the queries ask (`sliceProspectedBy()`). The aggregates also compute what their state offers, `possibleIntents()`, for the links of the API alone. Michel wants read models read for what they are, the aggregates telling their behaviour and no longer their state.

## Decision

- **A query reads its read model through a port** of the application of its context (`Application/Port/`), named after the read model it retrieves (`FoundOrganizationRetriever`), its implementation after its technology (`DynamoFoundOrganizationRetriever`, `InMemoryFoundOrganizationRetriever`); its handler presents what it read (ADR 0085). The adapter builds the whole read model, what the state offers included: a read model is plain data, with no behaviour.
- **A read model is named after what it is**, for the reading it serves (`FoundOrganizationPage` for a search, `OrganizationSheet` for the sheet), and replaces the `*Response` of its query. It is the output data of the query: it lives in the application, beside its query (`Application/SearchProspectedOrganizations/`), at the root of `Application/` when several queries share it; never in the domain, which keeps the rules and knows no reading.
- **The adapter reads the items of the table of the aggregate** (ADR 0048), mapping the document to the read model without restoring the aggregate: no table nor projection dedicated to reading.
- **What the state offers is a pure rule of the domain**, a policy (`Domain/Policy/`, ADR 0086: `OrganizationPossibilities`, `ExchangePossibilities`, `AccountPossibilities`, `PersonPossibilities`, and what a reader reads, `ExchangeReading`, `PersonReading`), given the facts it depends on, as native types or value objects of the domain, and answering intents of the domain: it never knows a read model, the domain depending on nothing outside it. The aggregate gives it its own state before each intention and refuses what it does not offer (`NotPossibleInItsState`, 409); the retriever gives it the facts of the document it read, and writes the answer in the read model it builds. A possibility or a reading depending on the reader (the intents of a person in PersonalData, ADR 0031; the text flagged and the files hidden of an exchange, ADR 0078) is computed at the reading, for that reader: the retriever receives them.
- **The read model carries what is possible, never a link**: the presenter of the API turns it into `_links`, for the roles of the member (ADR 0081, unchanged).
- **An aggregate loses the getters that served the reading**; it keeps its identity and the questions that express a rule (`readsPersonalData()`, `hasContact()`). A repository keeps what the commands and the domain services ask; what only a query asks moves to its retriever.
- The scenarios of the core keep reading through the queries: the in-memory retrievers read the `InMemoryDatabase` the in-memory repositories write.
- Migrated query by query, beginning with a list.

## Consequences

- A list no longer restores its aggregates; a query reads what it shows.
- A rule of the state is written once, in its policy, tested alone; the aggregate and the read model cannot disagree on it.
- The document of an aggregate is mapped twice, written by its repository and read by its retrievers: a change of the document changes both.
- The read model no longer built from the aggregate, its mapping moves from the core to the adapters of the API (`OrganizationResponse::of()` disappears); the presenters, the resolvers and the voters take the read models in place of the responses.
- The facts the policy takes are listed twice, by the aggregate and by the retriever; a new fact fails both at the analysis.
- The scenarios read through the in-memory retrievers: that the real adapter asks the policy is tested by the API, on its table (`DynamoOrganizationSheetRetrieverTest`); the refusals of the aggregate stay tested in the core (`OrganizationIntentsTest`).

## Alternatives considered

- What the state offers written in the document at each save, read as it is: a change of a rule would need every item written again, and the possibilities depending on the reader cannot be kept. Michel chose the policy computed at the reading.
- The presenter deducing the links from the facts of the read model, the core keeping only its refusals: the rules would be repeated in the API (the most attachments of an exchange, the rights of a reader of PersonalData), and a link hidden by mistake would go unnoticed by the scenarios. Michel kept what is possible in the read model.
- The handler of the query asking the policy for the read model it read, so that the scenarios cover it: the read model would have needed a method to receive the answer, or each handler would have repeated the translation. Michel preferred the adapter building the whole data, no rule being broken, its tests moving to the API.

## Revisit when

- A screen needs data the table of the aggregate cannot give in one read: a projection of its own, kept by the events.
