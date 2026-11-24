# 0207. The front's tests run in VM threads

- Date: 2026-11-24
- Type: architecture
- Status: Accepted
- Implemented by: `apps/front/vite.config.ts` (the `test` block), `apps/front/stryker.config.json` (`vitest.pool`), `apps/front/Makefile` (`TEST_WORKERS`)

## Context

`make ci` lasted as long as the front's tests. In a worktree they took 452 s, and 894 s beside three other suites, against 113 s on the CI's runner. A worktree's `node` container has two processors (`docker-compose.agents.yml`). Vitest keeps one processor for itself, so the 122 test files ran in a single worker. Each file created its own jsdom, which took 30 % of a run. Stryker's first run of the tests paid the same cost.

## Decision

- Vitest runs the tests in the `vmThreads` pool. Each worker creates jsdom once. Each file still gets its own window and its own modules, in a VM context of its own.
- A worker is started again once it holds half of the container's memory (`vmMemoryLimit`).
- Vitest keeps its default number of workers, one processor fewer than the container has: one in a worktree, three on the CI's runner. `make test-front TEST_WORKERS=` sets another number.
- Stryker's Vitest runner chooses its own pool: `stryker.config.json` names `vmThreads` too.

## Consequences

- In a worktree, the suite takes 181 to 197 s alone and 523 s beside three others, all green. A worker does less work per file than before, so no test runs slower than it did. Stryker's first run on 294 tests took 148 s instead of 221 s.
- The test code runs in a VM context. An object made outside it fails an `instanceof` against a class of the context.
- A VM worker frees its memory less reliably. In a worktree, the worker reached 1.25 GB of the 2 GB bound. `lint/labels-headroom.test.ts` adds about 1 GB for a few seconds while it runs TypeScript.

## Alternatives considered

Measured on the worktree's container and weighed by the developer of the ticket:

- Two workers in a worktree, one per processor: 141 s alone, 385 s beside three others. Under `make ci`, the PHP checks take the processors, and each test ran up to twice as slow as with one worker. The first test of `booking-section.test.tsx` passed the 20 s timeout (ADR 0109), and `make ci` failed.
- Two workers with the default pool (`forks`): 335 s. jsdom was still created for each file.
- Sharing the environment and the modules between files (`isolate: false`): 61 test files mock modules (`vi.mock`). A module that one file mocks could reach another file and falsify its test.
- `happy-dom` instead of jsdom: once jsdom is created once per worker, the environment takes 2 % of a run.

## Revisit when

A test fails only in the VM context (a cross-realm `instanceof`), a worker of a worktree is killed at the memory bound, or the `node` container of a worktree gets more processors.
