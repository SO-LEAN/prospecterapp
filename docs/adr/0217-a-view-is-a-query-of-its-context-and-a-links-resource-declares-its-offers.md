# 0217. A view is a query of its context, and a links resource declares its offers

- Date: 2026-11-26
- Type: architecture
- Status: Proposed
- Supersedes: for "a view's offers are read from a presenter's links", [0213](0213-the-event-storming-is-one-wall-per-context-with-a-lane-per-module.md); for "a reader composing contexts keeps the first answers, then builds the presenter of the last one", [0085](0085-a-query-presents-its-answer-to-a-presenter-built-for-the-call.md)
- Implemented by: `apps/api/src/Shared/Resource/Offers.php`, the links resources of `apps/api/src/*/Resource`, `apps/api/tests/Base/OffersMatchTheirLinks.php`, `packages/phpstan-rules/EventStorming` (`ClassFactsCollector`, `ContextStorming`), `docs/architecture/api.md`, `docs/architecture/event-storming.md`; the views' queries and their retrievers in the later changes of the same ticket

## Context

The event storming read a view's offers in the texts that its presenter writes in the arguments of the objects it creates (ADR 0213). The API builds its links in four ways: a table of routes in a constant, a factory the presenter delegates to, a reader that asks several queries and builds the links itself, and routes written in clear. The analysis read only the last way. Prospecting showed 32 false "Offered where?" hotspots, PersonalData 7 and Accounts 3, and the real gaps were lost among them.

The readers also compose a view in the primary adapter. They chain queries of several contexts and build the resource. The view has no use case of its own, and the core does not know it. The root `CLAUDE.md` wants the API to adapt only. The core already has the pattern within one context: a query asks a `*Retriever` port for its read model, and an adapter of the API implements it (`persistence.md`).

## Decision

The maintainer took these decisions on the ticket:

- A view is a use case of the context that shows it: a query attached to that context. The list of a campaign's recipients, with each person's data, is a query of Campaigns. The query asks a retriever, a port of its application layer, for its read model. An adapter of the API implements the port and composes what the other contexts publish. The controller and the MCP tool call only this use case. When a materialized view fills the read model one day, only the adapter changes. No reader serves a view any more.
- Personal data are always composed from PersonalData, whatever the retriever's implementation: its published service, asked with the member who reads. PersonalData still decides who reads them, and forgets an erased person. No implementation keeps them outside PersonalData, a materialized view included.
- A links resource declares the route each intent link offers: `#[Offers('<route>')]` on its property, as OpenAPI is declared on the controllers. A property declares one route, or one route for each presenter that fills a shared links resource with its own route: `#[Offers('api_contacts_note_exchange', 'api_organizations_note_exchange')]`. Presenters, factories and tables of routes build their links as they want. The structure of the domain (commands, events, aggregates, policies) stays inferred from the types.
- An intent link is a link to a route whose controller takes `App\Shared\CommandBus`. A navigation link to a query needs no declaration. The API tests check each link they serialize. A declared link that leads to another route fails, and so does an undeclared intent link (`OffersMatchTheirLinks`).
- The analysis finds a view's links resources through the types: the resource that the presenter keeps, its typed properties and its lists (`list<X>`), down to the links resources and their `#[Offers]`. It follows no call, a factory's included. A links resource that two views hold counts as offered by both.
- A resource that no context owns, such as the member's sign-in (`SignInReader`, served by Cognito), stays off the walls and declares nothing.

## Consequences

- A wrong or missing declaration fails an API test that serializes the link. A link that no API test serializes is not checked.
- A nested resource or a list must be typed for the analysis to reach its links. The OpenAPI contract already needs these types.
- A links resource shared by several views offers its commands on each of them, even when one view never fills a link.
- Until a reader becomes a query of its context, the view it serves offers nothing on the wall. Its commands keep their "Offered where?".
- ADR 0215 keeps its decisions. Its argument against an attribute "written by hand that nothing checks" does not hold for `#[Offers]`, which the API tests check.

## Alternatives considered

- Following the presenter's calls into the factories and the tables of routes, as the analysis followed an entrance's calls. The architect raised it against the types. The maintainer chose the types: the ticket wanted no factory to follow.
- Constraining every presenter to build its links one way, so that the analysis could read them. The maintainer refused it: it would constrain all of the API's code for one tool.

## Revisit when

A link is built that no API test serializes, or a view's resource cannot be typed down to its links.
