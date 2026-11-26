# 0216. The event storming wall reads row by row, left to right

- Date: 2026-11-26
- Type: architecture
- Status: Accepted
- Supersedes: for "A step is a cluster of post-its that touch: the command, its aggregate, its event and the external systems it asks" and for "Each module is a lane of it", [0213](0213-the-event-storming-is-one-wall-per-context-with-a-lane-per-module.md). It replaces nothing of [0215](0215-the-event-storming-reads-the-console-the-daily-schedule-and-projections.md): what the platform operator and the daily schedule send still never asks "Offered where?".
- Implemented by: `packages/phpstan-rules/EventStorming` (`ClassFactsCollector`, `ClassFacts`, `Facts`, `ContextStorming`), `apps/front/event-storming` (`wall.ts`, `grid.ts`, `marker.ts`, `pictograms.ts`, `story.ts`, `page.ts`, `lettering.test.ts`), `docs/architecture/event-storming.md`, the walls

## Context

The maintainer read the seven walls and found that a row did not read alone:

- A human actor stood once per module, left of the first view it read. The other views and the commands had no actor, while the member's AI was a badge on each use case. A command whose actor read no view of its module hung from the actor, and an offer by a view of another module was lost.
- Arrows went back. A policy that sends several commands left them at the lane's top left, with arrows up to 1,400 px up. Prospecting's Exchange and Contact hear each other, so their lanes sent arrows up to 8,300 px up. A policy that hears the event its own command records stood left of it. The events of other contexts a policy hears stood in a file, so the first arrow ran under the second event.
- A command that changes an aggregate without an event, such as Process recipient of campaign exchange (`Campaign::process()`), showed no aggregate: only a method that records an event linked it.

The maintainer decided that the wall follows Brandolini's event storming strictly, and that a wall may grow as large as it takes, as long as it reads from left to right.

## Decision

- **Actors.** An actor is a standard post-it, against the left of each command it sends and each view it reads. When several actors send one command, their post-its stand one under the other: the people first, then the platform operator, then the daily schedule. A row reads view, actor, command, aggregate, event. The lone actor at a lane's head and its lines go. The member's AI alone stays a badge on each use case it calls: a deliberate extension of Brandolini's grammar, which the legend names.
- **Offers.** A view's line ends at the actor of each command it offers in its lane. A view of another lane offers by a pictogram on each side. A command a person sends that no view of the wall offers asks "Offered where?" when that person reads views on this wall.
- **No arrow back.** No arrow goes left and none climbs to a row above. The wall takes the room it needs in width and in height. A reaction stands right of what it hears, in its row or lower. An arrow to a lower row runs along the gaps between rows and down a corridor left of its end. A policy that hears the event its own command records waits for that event's copy in another step. The events of other contexts a policy hears stand one under the other.
- **Lanes that hear each other.** The lanes follow the reactions between them, with the modules' order for ties. In a cycle of any length, the lane whose reactions to the lanes left most outnumber those it hears from them comes first, again with the modules' order for ties. A reaction whose event stands in a lower lane moves into the event's lane with its whole step: its policy, its command, its aggregate, its event and its external systems. The reactions that step starts move by the same rule, until no reaction points to a lane above. Its card still names its module. The ticket left this rule to its architect, who settled it within the maintainer's rule: a policy stands right of its event, in the event's module or in a lower one.
- **A change without an event.** A public method of an aggregate that returns nothing and writes a property of the object, itself or through the class's other methods, changes the aggregate (command-query separation). A command that calls one is linked to the aggregate even when the method records no event, and the wall puts a "No event recorded" hotspot where the event would stand.

## Consequences

- Each row reads alone, and a check on every wall fails when an arrow goes left, climbs a row or runs under a post-it.
- The walls grow wider: a reaction stands right of what it hears, and an actor post-it adds a column to each row.
- A lane may hold a reaction of another module, and its card says so.
- The analysis reads only the methods a class declares. A change an aggregate takes from a trait or a parent is not seen.
- A guard that only throws returns nothing too. It writes no property, so it is no change.
- The new "No event recorded" hotspots (Campaigns' process, reopen and forget, Connectors' calendar entries) are real gaps of the domain, left to tickets of their own.

## Alternatives considered

For lanes that hear each other, the architect weighed drawing the back reaction only as a pictogram while its step stays in its module. That option lost because the reaction would no longer read after its event: Discard attachments of erased exchange must read after Exchange erased. For a change without an event, the developer first read every method returning `void` as a change. Guards such as `Organization::ensureHasContact()` then showed as changes, so the rule also requires a write to the object.

## Revisit when

A wall needs a corridor that the rows it crosses block, or a reaction moved into another lane misleads its reader.
