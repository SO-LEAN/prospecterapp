# The event storming, generated from the code

A context's event storming is drawn from its code, never by hand. Booking is the first context drawn, as a spike: the page shows what the code says today, and nothing checks yet that the committed page follows the code.

## What it shows

The code already follows the grammar of the event storming. Each post-it is read where the code says it:

| Post-it | Colour | Read from |
|---|---|---|
| Command | blue | the message of each `CommandHandler` |
| Aggregate | pale yellow | a class of `X/Domain/Model` whose method, called by a handler, records an event (its intention, ADR 0043) |
| Event | orange | a class of `X/Domain/Event` created there; a published one, of `X/Contract/Event`, says so |
| Reaction | lilac | each `Policy` and each `EventHandler`; another context's policy that subscribes to a published event (`apps/api/config/subscriptions.json`) |
| Read model | green | the query of each `QueryHandler`, and what its output port presents |
| External system | pink | a port of `X/Domain/Port` a handler or a service of the context takes, named after the contexts its adapters in the API read |
| Actor | yellow | the role a controller requires (`#[IsGranted]`), anyone when it requires none, and the member's AI through a tool of the MCP server |

- A command and a read model carry the title of their use case's feature. A feature names the use cases it describes by its tags, above its title: `@OpenBooking @CloseBooking`. The class name shows on hover.
- A link that happens only under a condition is dashed, never guessed: an event recorded in a branch, a loop, a closure or after an early return, and a command that a policy may not send.
- A view per module of `X/Application` shows its use cases, who sends them, what they change and what happens then. Each chain has its own copy of the aggregate. At its edge, faded, sit the events of another module or context that its reactions hear, the policies that send it a command from elsewhere, and the reactions of others to its events.
- The context's overview shows its modules, the events that go from one to another, and the use cases at the application's root. A context without modules shows all its chains there.

## How it is generated

`make event-storming CONTEXT=Booking` writes two files in `apps/front/public/docs/event-storming/booking/`. Commit both.

1. `model.json`, the model, read by PHPStan run from the API, whose vendor knows the core and the delivery (`make -C apps/api event-storming-model`). `packages/phpstan-rules/EventStorming/event-storming.neon` runs no rule of a level, only collectors:
   - `ClassFactsCollector`: what each class implements, handles, returns, depends on, the roles it requires and what its output port presents;
   - `CallsCollector`: each method call on the type it is called on, and each creation;
   - `ConditionalLinesCollector`: the lines of each method that run only under a condition.

   `EventStormingFactsRule` hands what they collected to `EventStormingFormatter` (`--error-format=eventStorming`), which gives it to `ModelDocument`. An analysis that read no facts, or could not read the code, writes no model and fails. `ContextStorming` follows the calls from each handler to the intentions of the aggregates, then to the events they record, and writes the model. Two generations of the same code give the same file.
2. `index.html`, the page, written by `apps/front/event-storming/generate.ts`, plain TypeScript that Node runs without a build (`make -C apps/front event-storming-page`). `views.ts` cuts the model into views, `layout.ts` lays each one out with ELK (elkjs, the layered algorithm, from left to right), `page.ts` writes a standalone page. It loads no outside script or stylesheet. It reads in light and dark, filters the post-its by type, and lights the arrows of a post-it on hover. It places the post-its to a tenth of a point.

The PHPUnit tests of the generator run with the core's (`packages/core/tests/PHPStan/EventStorming`), on the fixtures of `packages/phpstan-rules/fixtures/event-storming`. The page's are Vitest tests (`apps/front/event-storming/*.test.ts`).

## Where it is served

The page is in the front's public folder: every release holds it, under `/docs/event-storming/<context>/` (ADR 0066). The instance decides whether it serves it, never the build:

- on AWS, the CloudFront function of the release (`apps/front/infra/functions/release.js.tftpl`) answers a folder under `/docs/` by its `index.html` when the instance's Terraform sets `serves_the_documents = true` (dev), and 404 otherwise (prod), for a path of the release too;
- locally, nginx asks the dev server for a folder's `index.html` (`docker/nginx/templates/front.conf.template`): https://prospecter.localhost/docs/event-storming/booking/.

## What is left to the full tool

The other contexts, the big picture across the contexts, and a check in the CI that the committed pages follow the code.
