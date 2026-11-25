# The event storming, generated from the code

Each context's event storming is drawn from its code, never by hand, as one wall with a lane per module (ADR 0213). An overview of the contexts leads to every wall, and the CI fails when a committed wall no longer follows the code (ADR 0214).

## What it shows

The code already follows the grammar of the event storming. Each post-it is read where the code says it:

| Post-it | Colour | Read from |
|---|---|---|
| Command | blue | the message of each `CommandHandler` |
| Aggregate | pale yellow | a class of `X/Domain/Model` whose method, called by a handler, records an event (its intention, ADR 0043) |
| Event | orange | a class of `X/Domain/Event` created there; a published one, of `X/Contract/Event`, carries a `PUBLISHED` stamp |
| Policy | lilac | each `Policy` and each `EventHandler`; another context's policy that subscribes to a published event (`apps/api/config/subscriptions.json`) |
| Read model | green | the query of each `QueryHandler`, named after what its output port presents |
| External system | pink | a port of `X/Domain/Port` a handler or a service of the context takes, answered by the contexts its adapters in the API read, and by the published services (`Y/Contract/Service`) these adapters take |
| Actor | yellow | the role a controller requires (`#[IsGranted]`), anyone when it requires none, and the member's AI through a tool of the MCP server |
| Hotspot | magenta | a gap the wall shows: a command that records no event, a command nothing sends ("Nobody sends this"), a view nothing reads ("Nobody calls this"), a command no view of its actor offers ("Offered where?") |

### The words

- Every post-it is in English, from its intention's name, in sentence case: `AddInactivePeriod` reads "Add inactive period". An acronym keeps its capitals. A policy drops its `Policy` or `Handler` suffix, and reads "whenever <the event it hears>" above its name. A read model is named after what its query presents, or after its query without `Consult`. An actor is named by the role it holds: Member, Member's AI, Anyone with the link.
- The French title of a use case's feature stays apart, in the post-it's card, and the search reads it. A feature names the use cases it describes by its tags, above its title: `@OpenBooking @CloseBooking`. A use case no feature names keeps its English label alone. The generation prints each one, and each feature that names no use case: a warning, not a failure.
- A post-it has a short slug that a link to the wall names: `command:BookSlot`, `published:SlotBooked`, and its context first when it belongs to another one (`PersonalData/policy:IdentifyBookerPolicy`).

### What a view offers

A view offers the commands its presenter links to. The analysis reads, in each presenter of a query's output port, the texts written in the arguments of the objects it creates. A text that names a controller's route (`#[Route(name:)]`) leads to the commands that controller sends. The link is offered under a condition when its links resource may go without it (a nullable parameter). The condition is the sentence of the parameter's `#[OA\Property(description:)]` that starts with "Offered only" (`api.md`, "Links"). A condition the code does not write reads "only in some states".

### What nothing reaches

The analysis follows the calls it reads, so a dynamic call escapes it. What it finds no caller for is a suspicion to check, and its hotspot's card says so:

- a command that no controller, no tool of the MCP server and no policy sends carries "Nobody sends this". A console command (`app:*`) is no entrance the analysis reads;
- a view that no actor reads and that nothing takes in the other contexts carries "Nobody calls this". A published service is read by each class of another context that takes its service interface: the port that class implements, on the wall of its context, or the class itself when it implements no port.

### The wall

- One wall per context, and one lane per module of `X/Application` (ADR 0206), named on a tape. A context without modules (Reporting) has one lane, named after the context. A policy at the application's root stands in the lane of the command it sends. A module that hears another one sits under it.
- Time runs from left to right, but only for a real cause. Independent use cases stack in rows at the lane's left. A step moves right only when a reaction starts it, right of the event it hears.
- In a lane, the rows follow this order: the steps another lane starts, then each human actor with its views and its commands, by aggregate and then by name, then the commands no human sends, then the steps a reaction of this wall starts.
- A step is a cluster of post-its that touch: the command, its aggregate, its event, and the external systems it asks, tucked under it. Each step has its own copy of the aggregate.
- Only the reactions and a view's offers are lines. A human actor stands once in a lane, left of the view it reads, and a marker line branches from the view to each command it offers. An offer made only in some states is dashed, and its line shows the condition on hover and on focus. A command no view of its actor offers hangs from the actor, with an "Offered where?" hotspot. The member's AI is a badge on each use case it can call.
- An event of this wall heard by one of its policies carries a pictogram to that policy, and the policy one back. A post-it of another context stands on a piece of its paper.
- A pictogram leads to another context's wall, on the post-it that answers (ADR 0214): from a port to each published service that answers it, from a published service to each port that reads it, from a published event to each policy of another context that hears it, and from the event of another context a policy hears to that event. It is a link, `../<context>/#at=<slug>`, so Back returns. A port whose adapters take no published service names the contexts they read on hover.
- The post-its are square, on paper, shadowed, and lean by a hash of their slug. They are written in a handwriting font the page holds (Patrick Hand, OFL, `apps/front/event-storming/font/`).

### The page

The page moves a camera along the wall: zoom and pan, a minimap, a lane framed from its tape. A lane folds into a band that names its steps. A wall of more than 40 use cases (Prospecting) opens with its lanes folded, but the lane of the post-it its link names; showing a post-it unfolds its lane, and the link then keeps the lanes left folded (`#fold=…`, empty included). Compact rows turn the external systems into small named squares. Hovering, focusing or pinning a post-it lights its story from end to end, and pinning opens its card: its class, its module, its feature, what comes before and after, the view that offers it and the link to copy. Each jump and each pin is an entry of the history, so Back returns, to the wall it came from too: `#lane=BookingPage`, `#at=command:BookSlot`, `#fold=…`, `#rows=compact`. The search (`/`) finds a post-it by its label, its class or its feature's title. The legend is a filter, the wall also reads as text, and the lights go off.

## How it is generated

`make event-storming` writes every context's model and page in `apps/front/public/docs/event-storming/<context>/`, its SVGs in `docs/event-storming/<context>/`, and the overview in `apps/front/public/docs/event-storming/index.html`. `CONTEXT=Booking` writes only that context's wall. Commit them all.

1. The document of the models, read by PHPStan run from the API, whose vendor knows the core and the delivery (`make -C apps/api event-storming-model`). One analysis reads the whole core and the whole delivery (ADR 0214): the callers of a published service sit in the other contexts, and many controllers sit outside their context's folder of the API (`Contacts`, `Exchanges`, `Dashboard`). `packages/phpstan-rules/EventStorming/event-storming.neon` runs no rule of a level, only collectors:
   - `ClassFactsCollector`: what each class implements, handles, returns, depends on, the roles and the routes its attributes declare, its output port and what it presents, and the constructor's parameters that may be null with their description;
   - `CallsCollector`: each method call on the type it is called on, and each creation;
   - `ConditionalLinesCollector`: the lines of each method that run only under a condition;
   - `ArgumentsCollector`: the texts written in each argument of a created object, by the parameter they go to.

   `EventStormingFactsRule` hands what they collected to `EventStormingFormatter` (`--error-format=eventStorming`), which gives it to `ModelDocument`. An analysis that read no facts, or could not read the code, writes no document and fails. The document holds a model for each context whose application handles a message (the shared kernel aside), and the warnings of the business labels. `ContextStorming` follows the calls from each handler to the intentions of the aggregates, then to the events they record, reads what each view offers and who takes each published service, and writes the model. `Wording` gives each post-it its English label. Two generations of the same code give the same files.
2. The pages and the SVGs, written by `apps/front/event-storming/generate.ts`, plain TypeScript that Node runs without a build (`make -C apps/front event-storming-page`). `generation.ts` decides what it writes (which walls, in which folders, whether the overview, which SVGs a wall no longer draws), and `generate.ts` only reads and writes the files. Everything is laid out at generation:
   - `wall.ts` cuts the model into lanes and steps, `grid.ts` places them in roomy and compact rows with each lane's folded band, `lettering.ts` places every line of every label with the font's advances (`glyphs.ts`, `ink.ts`), `marker.ts` draws the leans and the marker lines, `story.ts` tells each post-it's story, its card and each step's sentence;
   - `page.ts` writes the standalone page with `wall.css` and the font. It loads no outside script, stylesheet or font. Its script is `wall-script.ts`, whose source the page writes with the marker's functions: it only moves the camera and the post-its;
   - `svg.ts` writes `wall.svg` and one SVG per lane, for the repository's documents. They hold the font, and GitHub shows them as images. A lane gone leaves no SVG behind;
   - `overview.ts` writes the overview: the contexts on a drawing, an arrow from one to another when the other's policies hear its events (the subscriptions, `apps/api/config/subscriptions.json`, gave each model these policies) or when its ports ask the other's published services, the same relations in two tables linked to their post-its, and each context's link to its wall.

### The check

`make check-event-storming` writes every wall again, then fails when a file under `apps/front/public/docs/event-storming` or `docs/event-storming` differs from the committed one, or is new, and names them (ADR 0214). It is the CI's `event-storming` check, which the changes to the core, the API, the generator, the walls and the PHP and Node images need (`make/changed-checks.sh`). `make ci` runs it before the front's tests, which read the models.

The generator's PHPUnit tests sit beside its code, in `packages/phpstan-rules/tests/EventStorming`, with their fixtures in its `Fixtures/`. The core's PHPUnit runs them (its `phpstan-rules` test suite), and its PHPStan, php-cs-fixer and Rector check them, the fixtures aside. Infection mutates the generator's PHP with the core. The wall's tests are Vitest tests (`apps/front/event-storming/*.test.ts`), mutated by Stryker in a run of their own (`stryker.event-storming.json`, `docs/architecture/tests.md`). `lettering.test.ts` checks that no label is cut by its paper or covered by a post-it painted above, leaning included, on every context's wall and on a method of 32 letters, in both densities.

## Where it is shown

- The repository's documents show the SVGs: `docs/README.md` links each context's wall, and each `docs/domain/<context>.md` shows its context's wall under its introduction, then links its lanes and the local page.
- The pages are in the front's public folder: every release holds them, the overview under `/docs/event-storming/`, the contents of the walls, and each wall under `/docs/event-storming/<context>/` (ADR 0066). The instance decides whether it serves it, never the build:
  - on AWS, the CloudFront function of the release (`apps/front/infra/functions/release.js.tftpl`) answers a folder under `/docs/` by its `index.html` when the instance's Terraform sets `serves_the_documents = true` (dev), and 404 otherwise (prod), for a path of the release too;
  - locally, nginx asks the dev server for a folder's `index.html` (`docker/nginx/templates/front.conf.template`): https://prospecter.localhost/docs/event-storming/.

## What is left to the full tool

The model holds neither the narrative order of a lane's steps nor the refusals. Only Booking's features name their use cases by their tags: the other walls show their English labels alone, and the generation lists them.
