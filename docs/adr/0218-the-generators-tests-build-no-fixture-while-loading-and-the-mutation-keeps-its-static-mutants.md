# 0218. The generator's tests build no fixture while loading, and the mutation keeps its static mutants

- Date: 2026-11-26
- Type: architecture
- Status: Accepted
- Implemented by: `apps/front/event-storming/*.test.ts`, `apps/front/event-storming/test/scripted-walls.ts`, `apps/front/vite.config.ts`, `apps/front/Makefile`, `make/mutation.mk`, `docker-compose.agents.yml`

## Context

A branch of the event storming's generator gave Stryker 629 mutants. 592 of them (94 %) were static, and the mutation took 56 minutes in 2 test runners. Stryker cannot tell which tests cover a static mutant, so it loads the modules again and runs every test against it. The ticket assumed the static mutants were the styles and the templates written at the top of the generator's files.

The measure said otherwise. `ink.ts` holds functions only, and all its 65 mutants were static. Stryker's Vitest runner records a test's coverage between `beforeEach` and `afterEach`. The generator's test files built their walls while they loaded, and the tests of the page's script built theirs in a `beforeAll`. So every line of the generator ran outside any test.

A worktree's node container has 2 processors. Stryker takes one runner per processor up to four, so it ran 2.

## Decision

- The generator's tests build their fixtures in a `beforeEach`, or in the test itself, never while their file loads.
- The tests of the page's script keep a `beforeAll`, since their tests follow one another on one page. They take their walls from a global setup of the `tools` project (`event-storming/test/scripted-walls.ts`, `provide` and `inject`). Vitest's main process runs it, where Stryker activates no mutant and records no coverage. The generator's mutation leaves that file out.
- A fixture built in a global setup needs a test of what its users read from it: `page-data.test.ts` compares the script's data with the page `make event-storming` wrote.
- `ignoreStatic` stays off. The constants left static (a size, a table, a style) are few, and they stay mutated.
- `MUTATION_WORKERS=<n>` runs Stryker in n runners. `INFECTION_THREADS=<n>`, given on the command line, runs Infection in n threads. Either gives the worktree's container n processors and n + 1 GB. Without them, the defaults hold.

## Consequences

- The same `ink.ts` run went from 65 static mutants in 5 min 09 s to none in 3 min 00 s, at the same score. A static mutant now costs the whole suite only when its line really runs at load.
- The tests of the page's script no longer kill the generator's mutants. Only the generator's own tests and `page-data.test.ts` judge the walls and their data.
- More processes cost more of the machine that the worktrees share: they are asked for, never the default.

## Alternatives considered

- Moving the styles, the templates and the presentation constants to files the mutation leaves out (the ticket's first option). It lost on the measure: a file of functions only was static all the same.
- `ignoreStatic` (the ticket's second option, offered by Stryker). It would have skipped every mutant the fixtures made static, so most of the generator would have been judged by no test.

## Revisit when

Stryker records coverage in `beforeAll` hooks or in a global setup, or the tests of the page's script can open a wall in each test.
