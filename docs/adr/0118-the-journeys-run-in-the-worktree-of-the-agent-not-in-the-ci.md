# 0118. The journeys run in the worktree of the agent developing, not in the CI

- Date: 2026-11-14
- Type: architecture
- Status: Accepted
- Supersedes: for the journeys being a check of the integration, [0116](0116-each-checkout-runs-its-own-stack-the-journeys-run-in-the-ci.md)
- Implemented by: `.github/workflows/ci.yml`, `Makefile` (`ci`, `check-e2e`), `make/changed-checks.sh`, `docker-compose.agents.yml`

## Context

The journeys became a check of the integration (0116): a pull request touching the code waited for the stack started on the runner, its data recreated, its figures counted and its 37 journeys, twelve minutes where the CI took two. Each agent now runs its own stack in its worktree (0116), so nothing keeps the journeys in the CI any more. The user does not want them there: the agent developing runs them.

## Decision

- The CI runs no journey: the workflow drops the check `e2e` that `make changed-checks` lists.
- In a worktree, `make ci` runs the journeys last when the changes need them (the core, the apps, the images of the stack): `check-e2e` deploys the stack of the worktree, recreates its data, then runs `make e2e`. The agent runs `make ci` before declaring its work done, so before pushing.
- In the main checkout, whose data `check-e2e` would recreate, `make ci` runs none and names `make e2e` on the running stack.

## Consequences

- A pull request is checked by the CI in a couple of minutes again.
- A journey broken by a change is caught only by the agent running `make ci` in its worktree: nothing on GitHub proves it ran. A change pushed without it reaches `dev` unchecked by the journeys.
- `make ci` in a worktree lasts the journeys too, when the changes need them.
- The MiniStack of a worktree may take 4 GB instead of 2 (`docker-compose.agents.yml`): the reactions of the journey stopping to prospect, all at once, took it beyond 2 GB, and the kernel killed it, the reactions and the functions lost. The CI, its MiniStack unbounded, never met it.

## Alternatives considered

- The journeys in the CI after the merge only (the pushes of `dev`), or on the pull requests carrying a label, or as a check not required: proposed by the assistant, set aside by the user, who wants them run by the agent developing.

## Revisit when

A journey broken on `dev` was pushed by a change whose journeys did not run.
