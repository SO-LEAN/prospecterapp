# 0215. The event storming reads the console, the daily schedule and projections

- Date: 2026-11-25
- Type: architecture
- Status: Accepted
- Implemented by: `packages/phpstan-rules/EventStorming` (`ClassFactsCollector`, `ContextStorming`, `ClassName`, `Wording`), `apps/front/event-storming/wall.ts`, `packages/core/tests/Architecture/ReportingRecordsNoEventTest.php`, `docs/architecture/event-storming.md`

## Context

The walls of ADR 0213 and 0214 flagged gaps that were not gaps:

- Nine commands carried "Nobody sends this". The console (`app:*`) sent six of them, and the daily schedule (`ScheduledTask`) sent two. Controllers and tools sent the last one, `CatchUpMailHistory`, through a named constructor of the command. The analysis read only controllers and the MCP server's tools as entrances, and it followed only `new`.
- Reporting's commands, and Campaigns' commands that record facts, carried "No event recorded". They change no aggregate by design: they keep another context's facts, and views read them (ADR 0103, 0141). In an event storming, such a projection reads event → policy → read model.

## Decision

- A class marked `#[AsCommand]` is an entrance. Its actor is the "Platform operator": the person who runs an instance, outside any account. A class that implements the delivery's `ScheduledTask` is an entrance too. Its actor is the "Daily schedule". The maintainer named both actors. A task that the console also runs has both actors.
- A call from an entrance to a method of a use case's message is followed into that method, up to the message's creation (a named constructor).
- A command is a projection when its handler reaches, through its context's classes, a call to the context's `Domain\Repository\FactRepository`. The model links the command to the read models of its module. The wall puts those views where an event would stand, without "No event recorded".
- The platform operator and the daily schedule look at no screen. What they send never asks "Offered where?".
- Reporting records no event, and an architecture test of the core keeps it so.

## Consequences

- A command that keeps facts points to every view of its module, not only to the views that read those facts. In Campaigns' `Tagging`, Record contact facts also points to the organization's tags.
- The recognition rests on a name that the core already gives a role, `FactRepository`. A projection that keeps its facts under another name is not recognized, and it carries "No event recorded" again.
- The generator recognizes a daily task by the short name of its interface, because it names no class of the delivery.

## Alternatives considered

The ticket left the recognition of a projection to the architect: either by the port of facts the handler takes, or by a marker in the core. The plan weighed a PHP attribute on the handler that names the view it keeps. It would be more precise. But someone would write it by hand and nothing would check it, as ADR 0213 said of an attribute on each controller. It would also be metadata for a tool inside the core. The architect kept the port.

## Revisit when

A projection keeps its facts under another name, or a module holds views that its projection does not keep and the wall misleads.
