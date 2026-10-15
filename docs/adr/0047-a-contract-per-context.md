# 0047. Each context keeps what it publishes in a Contract of plain data

- Date: 2026-10-16
- Status: Accepted
- Supersedes: where [0045](0045-published-language-between-contexts.md) and [0046](0046-integration-events-between-contexts.md) put the published queries and events (`Application/Published`)
- Implemented by: `packages/core/src/*/Contract`, `packages/core/tests/Architecture/ContractTest.php`, the `BoundedContextsTest` of the core and of the API

## Context

The published queries and integration events of a context lived in `Application/Published`, among the use cases, where they got lost. Some depended on the domain of their context (`MemberResponse::of(Member)`, `ContactCollected::of()` taking the domain event), and their handlers stood next to them: they could not leave the core as the contract package a context deployed apart will need (0045).

## Decision

- Each context keeps what it publishes in `<Context>/Contract`, next to `Domain` and `Application`, singular like the other folders: `Contract/Service/<Query>` for the published queries and their answers (the open host service), `Contract/Event` for the integration events.
- A Contract is plain data and imports nothing, not even its own context (`ContractTest`): the handlers of the published queries are use cases of `Application` (`Application/ConsultMember/ConsultMemberHandler`), and the handlers build the answers and the integration events themselves.
- `BoundedContextsTest` allows nothing of another context but its `Contract`: in the core its `Contract/Event` (the queries go through a port), in the API and the doubles of the ports its whole `Contract`.

## Consequences

- What a context promises to the others is in one folder, apart from the use cases.
- Deployed apart, `<Context>/Contract` becomes the contract package of the context as it is.
- Building the answers and the events moves from named constructors to the handlers.
