# 0084. A query presents its answer to a presenter built for the call; a service is stateless

- Date: 2026-10-26
- Status: Accepted
- Supersedes: for what a query handler returns, [0029](0029-cqrs-with-a-synchronous-messenger-command-bus.md) and [0035](0035-plain-requests-and-responses-typed-refusals.md); specifies [0044](0044-dependencies-are-injected.md) for the objects of one call
- Implemented by: `packages/core/src/SharedKernel/Application/{OutputPort,QueryHandler,RefusalTranslatingHandler}.php`, the `*Output` and `Kept*` of each query (`packages/core/src/*/Application/*/`, `packages/core/src/*/Contract/Service/*/`, `Prospecting/Application/{TimelineOutput,KeptTimeline}.php`), `packages/core/tests/Acceptance/Application.php`, `packages/phpstan-rules/DependenciesAreInjectedRule.php`, the presenters and readers of `apps/api/src`

## Context

A query handler returned its response; the controller passed it to a presenter of the API, a service injected and stateless (`present(OrganizationResponse): OrganizationResource`). The presenter of Robert C. Martin is the other way round: the use case presents its answer to an output port, which the presenter implements and where it keeps its view model, the state of one call. Such an object cannot be injected, and ADR 0044 forbade building in the code a class receiving services (review of the pull request #71). Michel wants a service stateless, an in-memory cache aside, and an object keeping the state of a call out of the dependency injection.

## Decision

- **A service is stateless**, built by the container only (ADR 0044). **An object keeping the state of a call is no service**: it is built where it is used, and may receive the services of who builds it.
- **Every query presents its answer** to the output port its client gives, whoever the client is: a controller, a reader, the resolver of a voter (ADR 0081), an anticorruption layer asking a published query (ADR 0045), the scenarios. `__invoke(ConsultOrganization $query, ConsultOrganizationOutput $output): void`; `RefusalTranslatingHandler` passes it on. A command presents nothing (ADR 0029).
- **Each query declares its output port** beside it (`ConsultOrganizationOutput::present(OrganizationResponse)`), extending the marker `OutputPort` of the shared kernel; the queries answering a `TimelineResponse` share `TimelineOutput`. The ports of the published queries extend nothing, a Contract importing nothing (ADR 0047).
- **The core gives, beside each port, the presenter keeping the answer as it is** (`KeptOrganization`, `KeptMember`...), for the clients reading it rather than showing it: the resolvers, the anticorruption layers and their doubles, the readers composing several contexts, the scenarios.
- **A presenter of the API implements the output port** of the query whose answer it turns into a resource, and keeps that resource (`OrganizationResourcePresenter`, `TimelinePresenter`, `OrganizationPagePresenter`, `ExchangeDetailPresenter`, `AccountResourcePresenter`, `ContactResourcePresenter`, `OrganizationTimelinePresenter`). The controller or the reader builds it with the services it needs and the answers already read (the participants of an exchange, my membership, the persons of a slice), passes it to the query, then answers with its resource; an answer read before (by a resolver) is presented to it directly. A stateless mapper presenting no query is a factory (`TimelineResourceFactory`, `ContactInListResourceFactory`), a reading outside the core a reader (`SignInReader`).
- `DependenciesAreInjectedRule` allows building a class implementing `OutputPort`.

## Consequences

- The use case decides what it presents and when; the presenter holds the state of one call and is never shared between requests.
- One interface and one keeper per query; a client reads an answer in two lines (`$kept = new KeptOrganization()`, then `$kept->organization`).
- The keepers of the published queries live in their Contract: plain data with one method to receive it.
- A reader composing contexts keeps the first answers, then builds the presenter of the last one with them.

## Alternatives considered

- The clients that show nothing (the resolver of a voter, the anticorruption layers) keeping a return value of the query: Michel wanted a presenter for every query.
- The presenter keeping the answer written by each client (the API, the scenarios, the doubles): the same class three or four times; Michel chose one per query in the core.
- An attribute marking an object of a call for the PHPStan rule: the output port of the core already marks it, every query having one.

## Revisit when

- An object of a call other than a presenter needs services built in the code.
