# 0020. Use cases as commands on a command bus, no query bus

- Date: 2026-10-15
- Status: Proposed
- Implemented by: not yet; scenarios already independent of the facade (0017)

## Context

The API calls use cases through `UseCasesFacade`: a magic `__call` that finds the use case by name and checks the consumer's roles. The intent is to move to CQRS.

## Decision

- Use cases that change something become commands handled through a command bus; role checks move to a middleware of the bus.
- No query bus: reads stay direct calls (judged useless indirection).
- Scenarios do not depend on the facade: only `tests/Acceptance/Application` will change, the features and contexts stay.

## Consequences

- To decide when implementing: which bus, where handlers live, how commands return what a presenter needs (or how reads follow a command), transactions in a middleware.
- `UseCasesFacade` and its `@method` list disappear; controllers send commands.

## Alternatives considered

- A query bus as well: judged useless by the user; reads stay direct calls.

## Revisit when

Before implementing it: turn this record into an accepted one with the chosen bus and the answer to "what does a command return".
