# 0122. The journeys keep only what no other test links; the screens are tested by Vitest

- Date: 2026-11-14
- Type: architecture
- Status: Accepted
- Supersedes: for stopping to prospect and the dashboard running alone, and no journey running together reading the figures, [0117](0117-the-journeys-run-together-but-those-that-must-run-alone.md)
- Implemented by: `apps/front/e2e` (the journeys removed, `exchanges.spec.ts` reduced to its file, `dashboard-figures.spec.ts` on the founder of the run, `signInOncePerFile` of `sign-in.ts`, the time of the main journey and of the file, `keepToTheStack` of `fixtures.ts`, `wait-for-the-stack.ts`), `apps/front/playwright.config.ts`, `Makefile` (`check-e2e`), `apps/front/src/test/render-the-spa.tsx` and the tests taking over the checks of the journeys removed, `apps/api/tests/Api/CalendarApiTest.php`

## Context

38 journeys in 19 files: `make check-e2e` lasted some four minutes on the stack of a worktree as it runs, seven from nothing, one checkout at a time on the machine (0121). With several agents developing, the journeys set the pace. They caught few bugs of the product; their failures came from the infrastructure or their own fragility (the memory of MiniStack, waits outlasted, the download of a file). A good part of them checked a screen, what a component test of Vitest checks in seconds, and the rules they crossed were already tests of the API.

## Decision

- A journey stays when what it checks crosses parts no other test links: the sign-in on Cognito, the API in its Lambda, a reaction of the messaging (outbox, relay, worker), a file of the storage, a map rendered by MapLibre, or what only a real browser computes (styles, layout, the gestures of a phone). The others are replaced by tests of Vitest (a component with Testing Library, or the SPA on its routes, `src/test/render-the-spa.tsx`, the API doubled by `fetch`), or by tests of the API when they check a rule of the API; each check of a journey removed is named with the test taking it over in its pull request. The browser mode of Vitest is not used: the checks of styles and layout stay journeys.
- Removed: the calendar, the timeline of an organization, planning and handling planned exchanges, the route, completing a profile, the reminders, the to-dos. Kept: the main journey, the gestures of the console, the figures of the dashboard, stopping to prospect an organization of many exchanges, a file joined to an exchange, the maps, the phone, the sign-in screen, the shell and its cursors, the guard of the hosts outside the stack.
- Only the main journey runs alone, its member signed out everywhere. The dashboard counts its figure in the account the console opens for the run, which no other journey changes, its founder signing in there; the figures `check-e2e` counts from nothing are counted two minutes after the journeys start (0106), once those running together are done. Stopping to prospect, the longest of the journeys running together, starts first: its project `together-longest` is listed before `together`, Playwright running the projects of a phase in the order of the configuration.
- The journeys of a file checking what a screen shows signed in (the maps, their popups, the phone) sign in once for the file: the session the SPA keeps in the storage of its tab is given to the page of each journey before it loads.
- The main journey (fourteen steps, some 25 seconds) and the journey of the file, beside the reactions of stopping to prospect, have a minute each; the file is awaited once noted, sent while the timeline shows it.
- The global setup warms as many functions of the API as the journeys ask at once, five a page (the card of an organization) per worker: with three, a page asking more started a container in the middle of a journey, some ten seconds, the cause of the timeouts of the journey of the exchanges.

## Consequences

- 26 journeys in 12 files; `make check-e2e` lasts some 140 seconds on the stack as it runs (137 s, twice), against some four minutes, and some six minutes from nothing, against seven.
- A screen is tested where it is written, in seconds and without a stack; a change of a screen runs `make test-front`, the journeys only when it crosses one of the parts above.
- The journeys no longer prove that the screens are wired to the API they double: the contract typed in the front (0008) and the main journey do.
- Ten functions of the API stay warm while the journeys run.

## Alternatives considered

- The browser mode of Vitest, to take the checks of styles, layout and maps out of the stack: set aside by the user, the journeys keeping them.
- Running the reactions synchronously during the journeys: set aside by the user, the journeys proving the messaging as it runs.
- Three workers: a journey of the maps failed, MiniStack the bottleneck.

## Revisit when

`make check-e2e` lasts again more than two minutes on the stack as it runs, or a bug of the wiring between a screen and the API reaches `dev` with its Vitest tests green.
