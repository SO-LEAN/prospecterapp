# 0094. A handler is decorated by the decorator of its kind, and its client depends on the interface of the core

- Date: 2026-11-08
- Type: architecture
- Status: Accepted
- Supersedes: for the single decorator `RefusalTranslatingHandler` and the type the clients of a query ask for, [0035](0035-plain-requests-and-responses-typed-refusals.md) and [0044](0044-dependencies-are-injected.md)
- Implemented by: `packages/core/src/SharedKernel/Application/RefusalTranslating{Command,Query}Handler.php`, `apps/api/src/Shared/DependencyInjection/RefusalTranslatingHandlersPass.php`

## Context

One decorator, `RefusalTranslatingHandler`, translated the refusals of every handler, command or query. It implemented neither `CommandHandler` nor `QueryHandler` and took `__invoke(object $request, ?object $output = null)`: the output port a query requires (ADR 0085) became optional, a command could be given one, and the clients of a query depended on that concrete class of the core, `RefusalTranslatingHandler $consultOrganization`, rather than on the interface of the use case.

## Decision

- Two decorators, `RefusalTranslatingCommandHandler implements CommandHandler` (`__invoke(object $command)`) and `RefusalTranslatingQueryHandler implements QueryHandler` (`__invoke(object $query, object $output)`), each with the signature of the interface it decorates.
- The API asks for a query by the interface of the core named after its intent, `QueryHandler $consultOrganization`; the pass aliases the decorated handler to it, never the bare one.

## Consequences

- PHPStan refuses a query asked without its output port; a client no longer knows the decorator.
- An argument `QueryHandler` with no intent of that name fails the compilation of the container: no undecorated handler can be injected by mistake.
- The translation of the refusals is written twice, a `try`/`catch` of three lines.
- The decorators implement the interfaces tagged by `_instanceof`: they are not loaded as services (the resource of the core reads only the `Application` of the contexts), the pass registers them.

## Alternatives considered

One decorator implementing both interfaces: refused, an object would be both a command and a query.

## Revisit when

A third kind of handler called by a client appears.
