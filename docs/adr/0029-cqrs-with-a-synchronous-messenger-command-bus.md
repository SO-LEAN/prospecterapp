# 0029. CQRS: commands on a synchronous Messenger bus return nothing, only queries return data

- Date: 2026-10-15
- Status: Accepted, what a query returns superseded by [0084](0084-a-query-presents-its-answer-to-a-presenter-built-for-the-call.md)
- Supersedes: [0020](0020-command-bus-instead-of-the-facade.md)
- Implemented by: `packages/core/src/Application/*/*/` (`*Handler`, `CommandHandler`), `apps/api/config/packages/messenger.yaml`, `apps/api/src/Messenger/CommandBus.php`, `apps/api/src/Api/OrganizationReader.php`

## Context

0020 planned commands on a bus and no query bus, leaving open which bus and what a command returns. Use cases still returned data through presenters after changing the state, and were called through `UseCasesFacade`. Roles had meanwhile moved to the controllers (0026). The user wanted to push CQRS: only a read use case returns data.

## Decision

- One package per use case (`Organization/Relocate/`). A command (`Relocate`) is handled by a `RelocateHandler` implementing the marker `CommandHandler`; it changes the state and returns nothing. A query (`ConsultOrganization`) is handled by a `ConsultOrganizationHandler` that returns a response; the core has no presenter interface any more.
- The API sends commands on a Symfony Messenger bus with no transport: handled synchronously, within the request. `Messenger\CommandBus` throws a refusal of the core back as is. Every `CommandHandler` is tagged `messenger.message_handler`. Queries are called directly, no query bus.
- A controller answers a change with the fresh resource: it dispatches the command, then reads with the query (200, 201 for a creation). A creation gets its identity from the repository before the command.
- `UseCase`, `UseCasesFacade`, `UseCasePass` and `app:use-cases` are gone (`debug:messenger` lists the commands).

## Consequences

- A command cannot leak data; reads go through one place per resource.
- Refusals stay synchronous: the forms still show a 404, 409 or 422 in the response.
- Making a command asynchronous later is a routing of Messenger, but its screen would then need a 202 and a way to learn the outcome.
- One more read per change (the query after the command).

## Alternatives considered

- Fire and forget with an asynchronous resolution (202, then a subscription to learn when the resource is updated): raised by the user, judged too complex for now (transport, workers, errors reported out of band, eventual consistency in the front).
- Answering a command with `204 No Content` and letting the front read again: offered, the user chose 200 with the resource.
- A small bus written in the core instead of Messenger: offered, the user chose Messenger.
- A query bus: judged useless by the user (0020).

## Revisit when

A command takes long (an external call such as geocoding) or the load requires queues: route that command to a transport and give its screen an asynchronous outcome.
