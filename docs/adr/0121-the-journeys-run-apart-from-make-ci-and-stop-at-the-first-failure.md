# 0121. The journeys run apart from make ci, once a development is done, and stop at the first failure

- Date: 2026-11-14
- Type: architecture
- Status: Accepted
- Supersedes: for `make ci` running the journeys in a worktree, [0118](0118-the-journeys-run-in-the-worktree-of-the-agent-not-in-the-ci.md)
- Implemented by: `Makefile` (`ci`, `check-e2e`, `e2e`, `stop`, the lock of the journeys), `make/php.mk`, the Makefiles of the projects (`FAILED`, `FAIL_FAST`), `apps/front/e2e/handle-planned-exchanges.spec.ts`, `docker-compose.agents.yml`

## Context

`make ci` ran the journeys in a worktree (0118): every run of it lasted some 10 minutes alone, 18 beside another agent, where its checks take 3. The user had asked that the agent developing run the journeys, not that `make ci` run them. Two agents worked at once on a first trial: each failure, a journey timing out or a remark of the review, made them run the whole `make ci` again, three times for one ticket, and a run went to its end long after its first failure. The MiniStack of a worktree was killed once beyond its 8 GB, during the reactions of stopping to prospect.

## Decision

- `make ci` runs the checks only, everywhere, and stops at their first failure; it names `make check-e2e` when the changes need the journeys.
- `make check-e2e` runs the journeys, in a worktree, once the development is done, and again only when the code changes afterwards. On the stack running, as it is: the code is mounted in the functions, and a journey passes whatever the data. With `FRESH=1`, or no stack running, from nothing: the stack started again (MiniStack without the memory a former run left it), deployed, its data recreated, the first time once the development is done, and after a change of the functions, of their settings or of the subscriptions; the runs after a fix, on the stack as it runs. It prepares the journeys once for both projects, stops at the first failure and does not run the journeys running alone when one running together failed.
- The journeys of the machine run one checkout at a time: `make e2e` and `make check-e2e` wait for a lock in the git directory every checkout shares. Beside those of another checkout, they outlast their waits; no agent has to give the others a turn.
- A failure is run again alone: `FAILED=1` reruns what failed last (Behat, Playwright) or first (PHPUnit, Paratest), `FAIL_FAST=1` stops at the first failure (Behat, PHPUnit, Paratest, Vitest, Playwright).
- The journey of the planned exchanges plans its call through the API, due in half a minute instead of two: it lasted 116 seconds, the longest of the journeys running together.
- The MiniStack of a worktree may take 12 GB.

## Consequences

- A failing test or journey costs its own run, a few seconds to a minute, no more the whole `make ci`.
- `make check-e2e` on the stack running lasts some four minutes, against seven from nothing; the data of the journeys gather from one run to the other, and MiniStack keeps its memory until `FRESH=1`.
- Several failures are found one after the other.
- Nothing runs the journeys unless the agent runs `make check-e2e`: its output is their only proof.

## Alternatives considered

- Keeping the journeys in `make ci`, only one agent at a time running it: what the first trial did; the queue and the reruns cost more than the checks. Set aside by the user.

## Revisit when

A journey broken on `dev` was pushed by a change whose `make check-e2e` did not run.
