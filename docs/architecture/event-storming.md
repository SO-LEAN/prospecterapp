# The event storming, generated from the code

A context's event storming is drawn from its code, never by hand, as one wall with a lane per module (ADR 0213). Booking is the first context drawn: its wall shows what the code says today, and nothing checks yet that the committed files follow the code.

## What it shows

The code already follows the grammar of the event storming. Each post-it is read where the code says it:

| Post-it | Colour | Read from |
|---|---|---|
| Command | blue | the message of each `CommandHandler` |
| Aggregate | pale yellow | a class of `X/Domain/Model` whose method, called by a handler, records an event (its intention, ADR 0043) |
| Event | orange | a class of `X/Domain/Event` created there; a published one, of `X/Contract/Event`, carries a `PUBLISHED` stamp |
| Policy | lilac | each `Policy` and each `EventHandler`; another context's policy that subscribes to a published event (`apps/api/config/subscriptions.json`) |
| Read model | green | the query of each `QueryHandler`, named after what its output port presents |
| External system | pink | a port of `X/Domain/Port` a handler or a service of the context takes, answered by the contexts its adapters in the API read |
| Actor | yellow | the role a controller requires (`#[IsGranted]`), anyone when it requires none, and the member's AI through a tool of the MCP server |
| Hotspot | magenta | a gap the wall shows: a command that records no event, a view nobody is seen reading, a command no view of its actor offers |

### The words

- Every post-it is in English, from its intention's name, in sentence case: `AddInactivePeriod` reads "Add inactive period". An acronym keeps its capitals. A policy drops its `Policy` or `Handler` suffix, and reads "whenever <the event it hears>" above its name. A read model is named after what its query presents, or after its query without `Consult`. An actor is named by the role it holds: Member, Member's AI, Anyone with the link.
- The French title of a use case's feature stays apart, in the post-it's card, and the search reads it. A feature names the use cases it describes by its tags, above its title: `@OpenBooking @CloseBooking`.
- A post-it has a short slug that a link to the wall names: `command:BookSlot`, `published:SlotBooked`, and its context first when it belongs to another one (`PersonalData/policy:IdentifyBookerPolicy`).

### What a view offers

A view offers the commands its presenter links to. The analysis reads, in each presenter of a query's output port, the texts written in the arguments of the objects it creates. A text that names a controller's route (`#[Route(name:)]`) leads to the commands that controller sends. The link is offered under a condition when its links resource may go without it (a nullable parameter). The condition is the sentence of the parameter's `#[OA\Property(description:)]` that starts with "Offered only" (`api.md`, "Links"). A condition the code does not write reads "only in some states".

### The wall

- One wall per context, and one lane per module of `X/Application` (ADR 0206), named on a tape. A policy at the application's root stands in the lane of the command it sends. A module that hears another one sits under it.
- Time runs from left to right, but only for a real cause. Independent use cases stack in rows at the lane's left. A step moves right only when a reaction starts it, right of the event it hears.
- In a lane, the rows follow this order: the steps another lane starts, then each human actor with its views and its commands, by aggregate and then by name, then the commands no human sends, then the steps a reaction of this wall starts.
- A step is a cluster of post-its that touch: the command, its aggregate, its event, and the external systems it asks, tucked under it. Each step has its own copy of the aggregate.
- Only the reactions and a view's offers are lines. A human actor stands once in a lane, left of the view it reads, and a marker line branches from the view to each command it offers. An offer made only in some states is dashed, and its line shows the condition on hover and on focus. A command no view of its actor offers hangs from the actor, with an "Offered where?" hotspot. The member's AI is a badge on each use case it can call.
- An event of this wall heard by one of its policies carries a pictogram to that policy, and the policy one back. A port answered by another context carries a pictogram that names it on hover. A post-it of another context stands on a piece of its paper.
- The post-its are square, on paper, shadowed, and lean by a hash of their slug. They are written in a handwriting font the page holds (Patrick Hand, OFL, `apps/front/event-storming/font/`).

### The page

The page moves a camera along the wall: zoom and pan, a minimap, a lane framed from its tape. A lane folds into a band that names its steps. Compact rows turn the external systems into small named squares. Hovering, focusing or pinning a post-it lights its story from end to end, and pinning opens its card: its class, its module, its feature, what comes before and after, the view that offers it and the link to copy. Each jump and each pin is an entry of the history, so Back returns: `#lane=BookingPage`, `#at=command:BookSlot`, `#fold=…`, `#rows=compact`. The search (`/`) finds a post-it by its label, its class or its feature's title. The legend is a filter, the wall also reads as text, and the lights go off.

## How it is generated

`make event-storming CONTEXT=Booking` writes the model and the page in `apps/front/public/docs/event-storming/booking/`, and the SVGs in `docs/event-storming/booking/`. Commit them all.

1. `model.json`, the model, read by PHPStan run from the API, whose vendor knows the core and the delivery (`make -C apps/api event-storming-model`). `packages/phpstan-rules/EventStorming/event-storming.neon` runs no rule of a level, only collectors:
   - `ClassFactsCollector`: what each class implements, handles, returns, depends on, the roles and the routes its attributes declare, its output port and what it presents, and the constructor's parameters that may be null with their description;
   - `CallsCollector`: each method call on the type it is called on, and each creation;
   - `ConditionalLinesCollector`: the lines of each method that run only under a condition;
   - `ArgumentsCollector`: the texts written in each argument of a created object, by the parameter they go to.

   `EventStormingFactsRule` hands what they collected to `EventStormingFormatter` (`--error-format=eventStorming`), which gives it to `ModelDocument`. An analysis that read no facts, or could not read the code, writes no model and fails. `ContextStorming` follows the calls from each handler to the intentions of the aggregates, then to the events they record, reads what each view offers, and writes the model. `Wording` gives each post-it its English label. Two generations of the same code give the same file.
2. The page and the SVGs, written by `apps/front/event-storming/generate.ts`, plain TypeScript that Node runs without a build (`make -C apps/front event-storming-page`). Everything is laid out at generation:
   - `wall.ts` cuts the model into lanes and steps, `grid.ts` places them in roomy and compact rows with each lane's folded band, `lettering.ts` places every line of every label with the font's advances (`glyphs.ts`, `ink.ts`), `marker.ts` draws the leans and the marker lines, `story.ts` tells each post-it's story, its card and each step's sentence;
   - `page.ts` writes the standalone page with `wall.css` and the font. It loads no outside script, stylesheet or font. Its script is `wall-script.ts`, whose source the page writes with the marker's functions: it only moves the camera and the post-its;
   - `svg.ts` writes `wall.svg` and one SVG per lane, for the repository's documents. They hold the font, and GitHub shows them as images.

The generator's PHPUnit tests sit beside its code, in `packages/phpstan-rules/tests/EventStorming`, with their fixtures in its `Fixtures/`. The core's PHPUnit runs them (its `phpstan-rules` test suite), and its PHPStan, php-cs-fixer and Rector check them, the fixtures aside. The wall's tests are Vitest tests (`apps/front/event-storming/*.test.ts`). `lettering.test.ts` checks that no label is cut by its paper or covered by a post-it painted above, leaning included, on Booking and on a method of 32 letters, in both densities.

## Where it is shown

- The repository's documents show the SVGs: `docs/README.md` links them, and `docs/domain/booking.md` shows the whole wall.
- The page is in the front's public folder: every release holds it, under `/docs/event-storming/<context>/` (ADR 0066). The instance decides whether it serves it, never the build:
  - on AWS, the CloudFront function of the release (`apps/front/infra/functions/release.js.tftpl`) answers a folder under `/docs/` by its `index.html` when the instance's Terraform sets `serves_the_documents = true` (dev), and 404 otherwise (prod), for a path of the release too;
  - locally, nginx asks the dev server for a folder's `index.html` (`docker/nginx/templates/front.conf.template`): https://prospecter.localhost/docs/event-storming/booking/.

## What is left to the full tool

The other contexts, the jumps to another context's wall, the big picture across the contexts, and a check in the CI that the committed files follow the code. The model holds neither the narrative order of a lane's steps, nor the refusals, nor the callers of a published service.
