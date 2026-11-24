# 0214. Every context has its wall, generated from the whole code and checked in the CI

- Date: 2026-11-24
- Type: architecture
- Status: Accepted
- Supersedes: for "Stryker looks for the tests in `src/` only", [0210](0210-the-front-tools-tests-run-in-forks.md)
- Implemented by: `packages/phpstan-rules/EventStorming`, `apps/front/event-storming`, `apps/front/stryker.event-storming.json`, `Makefile` (`event-storming`, `check-event-storming`), `make/changed-checks.sh`, `.github/workflows/ci.yml`, `docs/architecture/event-storming.md`

## Context

ADR 0213 drew Booking's wall alone. The other contexts are larger: Prospecting holds about a hundred use cases. A pictogram toward another context led nowhere. Nothing checked that a committed wall followed the code. Read alone, a context's folders missed what sits elsewhere: the callers of its published services are in the other contexts, and many controllers are outside their context's folder of the API (`Contacts`, `Exchanges`, `Dashboard`). Reporting's figures had no actor. The generator's code was mutated by neither Infection nor Stryker.

## Decision

- One analysis of the whole core and the whole delivery writes the document of every context's model. `make event-storming` writes every wall and the overview from it. `CONTEXT=` writes one wall, the analysis staying whole.
- A pictogram leads to the post-it of another context's wall by a link, `../<context>/#at=<slug>`: from a port to the published services that answer it, from a published service to the ports that read it, from a published event to the policies of another context that hear it, and from a heard event to its wall. Back returns, the page's button too when it was opened from another wall.
- A wall of more than 40 use cases opens with its lanes folded, but the lane of the post-it its link names.
- `/docs/event-storming/` is the contents: the contexts, what goes from one to another (the events heard, the published services asked), and a link to each wall.
- `make check-event-storming` writes every wall again and fails on a file that differs from the committed one, or a new one, naming them. It is the CI's `event-storming` check, like the contract's.
- Infection mutates the generator's PHP with the core. Stryker mutates the wall's TypeScript in a second run, with its own configuration and the tests of `event-storming/` only. ADR 0210's memory problem came from the tools' tests sharing the SPA's VM worker while ELK laid the views out. ELK is gone (ADR 0213), and the two runs never share a worker.

## Consequences

- The analysis reads the whole code: about a minute without its cache, and a change to the core or the API needs the check.
- A view or a command that the analysis finds no caller for carries a hotspot. A dynamic call escapes it, so each one is a suspicion to check.
- The front's mutation has two scores, the SPA's and the generator's.
- Only Booking's features name their use cases by their tags. The generation lists the others, and warns without failing.

## Alternatives considered

The plan first let Stryker's single run look for the tests beyond `src/`. The architect's review refused it against ADR 0210, and the second run replaced it. No other option was weighed.

## Revisit when

A wall of another shape no longer fits the grid, or the check slows the CI beyond its other checks.
