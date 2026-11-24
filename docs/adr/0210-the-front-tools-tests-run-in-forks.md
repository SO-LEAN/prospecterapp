# 0210. The tests of the front's tools run in forks, apart from the SPA's

- Date: 2026-11-24
- Type: architecture
- Status: Accepted
- Supersedes: for the tests of `event-storming/` and `infra/`, the pool of [0207](0207-the-front-tests-run-in-vm-threads.md)
- Implemented by: `apps/front/vite.config.ts` (the `test.projects`), `apps/front/stryker.config.json` (`vitest.dir`)

## Context

The front now holds code beside the SPA: the event storming's generator (`event-storming/`), which lays its views out with ELK, and the CloudFront function of the release (`infra/functions/`). Their tests ran in the same `vmThreads` pool as the SPA's (ADR 0207). In a worktree, `make ci` and `make check-front` then failed twice: the kernel killed the Vitest worker at the `node` container's 2 GB bound ("Memory cgroup out of memory", the worker at some 1.05 GB of anonymous memory, beside the other checks). The SPA's tests alone passed (119 files, 801 tests). ADR 0207 names this signal to reopen it.

## Decision

- Vitest runs two projects. `spa` (`src/`, `lint/`) keeps the `vmThreads` pool and its `vmMemoryLimit` (ADR 0207). `tools` (`event-storming/`, `infra/`) runs in the `forks` pool: each file in a process of its own, which gives its memory back when it ends.
- Stryker's Vitest runner gives every project its own pool, `vmThreads`. It looks for the tests in `src/` only (`vitest.dir`): it mutates only `src`, which the tools' tests do not cover.

## Consequences

- In a worktree, `make test-front` passes, 126 files and 834 tests, and `make check-front` passes with the build.
- The tools' tests pay a process and a jsdom per file. They are a few files.
- Stryker runs neither the tools' tests nor those of `lint/`, which kill no mutant of `src`.

## Alternatives considered

None other was weighed: the split keeps ADR 0207 whole for the SPA.

## Revisit when

The SPA's tests are killed at the memory bound too, or the tools grow to many test files.
