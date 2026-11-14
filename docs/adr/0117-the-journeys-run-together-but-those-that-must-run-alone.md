# 0117. The journeys run together, but those that must run alone; the figures are counted meanwhile

- Date: 2026-11-14
- Type: architecture
- Status: Accepted
- Implemented by: `apps/front/playwright.config.ts` (projects `together` and `alone`), `apps/front/Makefile` (`E2E_WORKERS`, `E2E_ARGS`), `Makefile` (`check-e2e`), `apps/api/Makefile` (`db FIGURES=later`), `apps/front/e2e/wait-for-the-stack.ts`

## Context

Once a check of the integration (0116), the journeys made its longest job: 12 minutes, 37 journeys one at a time in 5.6 minutes, and before them the two minutes the count of the figures waits (0106). They ran one at a time because they sign the same members of the demonstration in, and the main journey signs its member out everywhere (Cognito global sign-out). The user asked to run them together and to stop waiting for the figures before them.

## Decision

- The journeys run together, two at a time by default (`E2E_WORKERS`), each file in a worker of its own: they sign the same members in, each session its own tokens, and name what they create after the moment they run.
- Those that must run alone form the project `alone`, after the project `together`: the main journey, which signs its member out everywhere; the dashboard, which counts a figure of the whole account; stopping to prospect, which starts tens of reactions at once.
- `check-e2e` recreates the data without counting the figures (`make db FIGURES=later`), counts them while the project `together` runs, none of its journeys reading them, then runs the project `alone` once both are done.

## Consequences

- A journey running together must not read what the others change in the account: a figure, a list counted, the sessions of a member. Such a journey goes in `alone`.
- Two journeys meeting on the same screen of the same member (the calendar, the exchanges planned) find the items of the other: a journey looks for its own, by their name.
- `make e2e` runs both projects, `alone` after `together`: a failure of `together` skips `alone`, which `check-e2e` runs on its own.
- Before them, as many functions are warmed as the journeys running together open pages at once (three per worker): one more would start its container during a journey, longer than its wait for a screen; run together first in the CI, four journeys outlasted their wait so.
- `check-e2e` keeps the report and the traces of each project in a folder of its own (`E2E_RESULTS`), or the project `alone` would erase those of `together`.

## Alternatives considered

- A member per worker, registered by the console: the account of the demonstration has no administrator to register them, and an account per worker would lack the data of the demonstration some journeys read. Weighed by the assistant, set aside.
- Not counting the figures before the journeys at all: without a rebuild, Reporting has no live version and records no fact, the dashboard staying at zero. Set aside.

## Revisit when

A journey fails only when run together, or the journeys running alone become the longest part of the job.
