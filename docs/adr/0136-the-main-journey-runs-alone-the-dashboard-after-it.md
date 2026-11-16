# 0136. The main journey runs alone, the dashboard after it

- Date: 2026-11-16
- Type: architecture
- Status: Accepted
- Supersedes: for the dashboard running beside the main journey, [0122](0122-the-journeys-keep-only-what-no-other-test-links.md)
- Implemented by: `apps/front/playwright.config.ts` (the projects `alone` and `alone-dashboard`), `Makefile` (`check-e2e`)

## Context

Since 0122, the journey of the figures of the dashboard ran beside the main journey, in the project `alone` (two workers), to save its seven seconds. Once the journeys running together no longer failed (the worker bounded, 0131), the main journey was the one failing, at random among its steps (a button or a text not found in five seconds, the journey outlasting its minute), with no request in error and no lack of memory: on five runs of a branch and one of `dev`, it failed beside the dashboard and passed alone (1.1 min).

## Decision

- The main journey runs alone: the project `alone` holds it only.
- The journey of the figures of the dashboard runs after it, alone too, in the project `alone-dashboard`, which depends on `alone`.
- `make check-e2e` runs the two one after the other (`--project=alone --no-deps`, then `--project=alone-dashboard --no-deps`, each its folder of results), the second not run when the first failed.

## Consequences

- `make check-e2e` lasts some ten seconds more, the journey of the dashboard no longer hidden behind the main journey.
- A failure of the main journey can no longer come from a journey beside it.
- Rerunning the journey of the dashboard alone: `make e2e FAILED=1 E2E_RESULTS=alone-dashboard E2E_ARGS="--project=alone-dashboard --no-deps"`.

## Alternatives considered

- One worker for the project `alone`: Playwright would run its files in the order of their names, the dashboard first, where the maintainer wanted the main journey first.
- Raising the minute of the main journey: set aside by the maintainer, it would hide the wait rather than remove it.

## Revisit when

The main journey fails again running alone, or the journeys running alone grow beyond these two.
