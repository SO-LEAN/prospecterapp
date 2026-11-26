# 0217. A view is a query of its context, and a links resource declares its offers

- Date: 2026-11-26
- Type: architecture
- Status: Proposed
- Supersedes: for "a view's offers are read from a presenter's links", [0213](0213-the-event-storming-is-one-wall-per-context-with-a-lane-per-module.md); for "a reader composing contexts keeps the first answers, then builds the presenter of the last one", [0085](0085-a-query-presents-its-answer-to-a-presenter-built-for-the-call.md)
- Implemented by: `apps/api/src/Shared/Resource/Offers.php`, the links resources of `apps/api/src/*/Resource`, `apps/api/tests/Base/OffersMatchTheirLinks.php`, `apps/api/tests/Architecture/PiecesOffersMatchTheirContractsTest.php`, `packages/phpstan-rules/EventStorming` (`ClassFactsCollector`, `ContextStorming`), Prospecting's views' queries, `ListCampaignRecipients`, `IdentifyPersonsAndOrganizations` and its port `OrganizationIdentificationRetriever`, the rules on `TaggableContacts` in `SearchContactsByTags` and `ListCampaignRecipients`, the API's `*Composition` classes, `docs/architecture/api.md`, `docs/architecture/core.md`, `docs/architecture/event-storming.md`, `docs/architecture/persistence.md`, `docs/domain/personal-data.md`

## Context

The event storming read a view's offers in the texts that its presenter writes in the arguments of the objects it creates (ADR 0213). The API builds its links in four ways: a table of routes in a constant, a factory the presenter delegates to, a reader that asks several queries and builds the links itself, and routes written in clear. The analysis read only the last way. Prospecting showed 32 false "Offered where?" hotspots, PersonalData 7 and Accounts 3, and the real gaps were lost among them.

The readers also compose a view in the primary adapter. They chain queries of several contexts and build the resource. The view has no use case of its own, and the core does not know it. The root `CLAUDE.md` wants the API to adapt only. The core already has the pattern within one context: a query asks a `*Retriever` port for its read model, and an adapter of the API implements it (`persistence.md`).

## Decision

The maintainer took these decisions on the ticket:

- A view is a use case of the context that shows it: a query attached to that context. The query asks a retriever, a port of its application layer, for its read model (25/11).
- Asking another context **decides** or **enriches** (26/11, replacing for the enrichment the 25/11 decision that an adapter of the API composes the other contexts):
  - To decide is to ask another context something whose answer changes what the use case does or shows. It goes through a port of the core, which an anticorruption adapter of the API implements from the other context's published service. The rule is in the query or the command, and its feature describes it: whether a person is a contact of the account, whether a host may hold their bookers, a person searched first among the candidates of the organization identified.
  - To enrich is to add names, tags or a card next to what the context already decided. It is a composition: each context answers its own query, with its data, its links and identifiers, and a `*Composition` class of the API joins the answers by identifiers. An adapter of a retriever never enriches.
  - A composition holds no rule: no choice, no business default, no sort or filter that means something. It puts the answers side by side. A rule makes it a query of the core.
  - Filtering, sorting or slicing on another context's data is not composed afterwards. It is a decision, a port of the core, or later a projection.
- Personal data always come from PersonalData, asked with the member who reads, in a composition as in an adapter. PersonalData still decides who reads them, and forgets an erased person. No implementation keeps them outside PersonalData, a materialized view included. Between two contexts that could own a view, the one that avoids carrying personal data is preferred.
- A links resource declares the route each intent link offers: `#[Offers('<route>')]` on its property, as OpenAPI is declared on the controllers. A property declares one route, or one route for each presenter that fills a shared links resource with its own route: `#[Offers('api_contacts_note_exchange', 'api_organizations_note_exchange')]`. Presenters, factories and tables of routes build their links as they want. The structure of the domain (commands, events, aggregates, policies) stays inferred from the types.
- An intent link is a link to a route whose controller takes `App\Shared\CommandBus`. A navigation link to a query needs no declaration. The API tests check each link they serialize. A declared link that leads to another route fails, and so does an undeclared intent link (`OffersMatchTheirLinks`).
- The analysis finds a view's links resources through the types: the resource that the presenter keeps, its typed properties and its lists (`list<X>`), down to the links resources and their `#[Offers]`. It follows no call, a factory's included. A links resource that two views hold counts as offered by both.
- Each piece of a composition is a query of its context, with its presenter, its links and their `#[Offers]`. The composition is on no wall. A piece's presenter keeps its own context's links. When the contract's links resource mixes two contexts' links, each piece keeps a links resource of its own, which the composition copies property by property. A test checks that each piece's property declares the routes of the contract's property of the same name (`PiecesOffersMatchTheirContractsTest`).
- A resource that no context owns, such as the member's sign-in (`SignInReader`, served by Cognito), stays off the walls and declares nothing.

## Consequences

- A wrong or missing declaration fails an API test that serializes the link. A link that no API test serializes is not checked.
- A nested resource or a list must be typed for the analysis to reach its links. The OpenAPI contract already needs these types.
- A links resource shared by several views offers its commands on each of them, even when one view never fills a link.
- Until a reader becomes a query of its context, or the pieces of a composition, the view it serves offers nothing on the wall. Its commands keep their "Offered where?".
- The enrichment that Prospecting's queries received through `PersonDescriptionRetriever` before the 26/11 decision moves to compositions in a later change of the same ticket.
- A view that leaves out what another context no longer knows decides it in its query: the tag search and a campaign's recipients leave out the contacts PersonalData no longer knows, through `TaggableContacts`.
- ADR 0215 keeps its decisions. Its argument against an attribute "written by hand that nothing checks" does not hold for `#[Offers]`, which the API tests check.

## Alternatives considered

- Following the presenter's calls into the factories and the tables of routes, as the analysis followed an entrance's calls. The architect raised it against the types. The maintainer chose the types: the ticket wanted no factory to follow.
- Constraining every presenter to build its links one way, so that the analysis could read them. The maintainer refused it: it would constrain all of the API's code for one tool.

## Revisit when

A link is built that no API test serializes, or a view's resource cannot be typed down to its links.
