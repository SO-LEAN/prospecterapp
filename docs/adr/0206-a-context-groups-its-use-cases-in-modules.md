# 0206. A context groups its use cases in modules

- Date: 2026-11-24
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/src/Booking/Application/{BookingPage,PublicLink,Appointment}`, `apps/api/config/services.yaml` (the core's handlers), `SubscriptionsPass::TRANSLATOR`, `tests/Architecture/EventHandlersOfTheCore`, the API's `ReactionsAreSubscribedTest`

## Context

Each use case is a folder at the root of its context's `Application` (ADR 0029). Its folders sit next to the classes that several use cases share. Prospecting has 118 entries there, Campaigns 39 and Booking 18. Subjects that have nothing in common sit side by side (`PlanExchange`, `ChangeLogo`, `DismissPossibleDuplicate`). The event storming that will be generated from the code needs one view per subject of a context.

## Decision

- A context may group its use cases by subject in modules, Evans's word for a grouping inside a bounded context: `<Context>/Application/<Module>/<Intent>/`. A module is one level deep. It is named after its subject, a noun of the context's language. It is one view of the generated event storming.
- A use case that belongs to no module stays at `<Context>/Application/<Intent>/`.
- A class that the use cases of one module share sits at that module's root. A read model that a module's query shares with its port in `Application/Port` also sits there. A class that several modules share stays at the application's root.
- The role folders stay at the application's root, out of the modules: `Application/Port` (ADR 0088), and `Application/Policy` for the policies (ADR 0203).
- The domain is not grouped in modules.
- The contexts move one at a time, each in its own change. Booking is the first, with `BookingPage` (the host sets up their page), `PublicLink` (anyone holding the link views it and books a slot) and `Appointment` (what was booked, until its exchange is erased).

## Consequences

- What finds a handler by its folder reads one module level too: the API's service resource, its translator pattern (`Application/[<Module>/]Publish…/Publish…Handler`) and the architecture tests. A context then moves its use cases without touching the wiring.
- The namespaces of the moved use cases change in the core, its tests and the API. The routes, the events, their serialized form and the subscriptions do not change.
- A policy sits apart from the module of the command it returns. The generator reads that module from the policy's return type.
- A context under 20 entries may keep its use cases ungrouped.

## Alternatives considered

None was weighed. The ticket chose the word "module" over "subject" and "context". The architect accepted each context's module names in its plan.

## Revisit when

A module grows to the size that made the grouping necessary, or the generated event storming needs a view that a single level cannot hold.
