# 0213. The event storming is one wall per context, with a lane per module

- Date: 2026-11-24
- Type: architecture
- Status: Accepted
- Supersedes: for "a module is one view of the generated event storming", [0206](0206-a-context-groups-its-use-cases-in-modules.md)
- Implemented by: `packages/phpstan-rules/EventStorming`, `apps/front/event-storming`, `docs/event-storming/booking/`, `docs/architecture/event-storming.md`

## Context

The first generated event storming drew one view per module, laid out by ELK as a graph. On Booking it read badly:

- the labels were the French titles of the features, and two pairs of commands shared one;
- the post-its were flat rectangles, and the actors were labels;
- independent use cases stood from left to right, as if they followed each other in time;
- 53 arrows crossed the views.

The maintainer validated a static prototype on Booking's real model, in four iterations. Its choices are the rules below.

## Decision

- One wall per context. Each module is a lane of it, named on a tape. A deep link opens the wall on a lane or on a post-it.
- Time runs from left to right only for a real cause. Independent use cases stack in rows. A step moves right only when a reaction starts it, right of the event it hears.
- A step is a cluster of post-its that touch: the command, its aggregate, its event and the external systems it asks. Only the reactions and a view's offers are lines.
- Every post-it is in English, from its intention's name. The model writes the label, and it keeps the feature's French title apart.
- A view's offers are read from the API. A presenter's links name routes, and each route names a controller, which sends its command. An offer is conditional when its links resource may go without it. The condition is the sentence of its OpenAPI description that starts with "Offered only". Otherwise the wall says "only in some states".
- The generator computes the layout and every line of every label, on its own grid, so ELK goes. A test checks that no label is cut or covered.
- The generation also writes an SVG of the whole wall and one per lane in `docs/event-storming/<context>/`, for the repository's documents. The page stays the release's `/docs/` page (ADR 0066).
- The page and the SVGs hold a handwriting font (Patrick Hand, OFL), so they load nothing.

## Consequences

- One drawing serves the whole context and each module: the camera frames a lane, or a lane folds.
- The words come from the code, so a rename shows on the wall at the next generation.
- A links resource that writes its condition another way shows "only in some states" until a ticket touches it.
- The layout is the generator's own code: it must grow with the other contexts' shapes (Prospecting is larger), where ELK placed any graph.
- A label is measured with the font's advances, each rounded up to a whole pixel, as Chromium draws it. Another browser may draw a little narrower.
- Nothing yet checks in the CI that the committed files follow the code.

## Alternatives considered

The prototype weighed three sources for what a view offers. The API's links won. The front's screens (a TypeScript analysis of which route calls which mutation) cost too much for now. An attribute written on each controller would be written by hand, and nothing would check it.

## Revisit when

The other contexts are drawn, and a lane or a reaction no longer fits the grid's rules.
