# 0220. Pull requests to dev go through the merge queue, where the journeys run

- Date: 2026-11-26
- Type: architecture
- Status: Accepted
- Supersedes: for the journeys being no check of the CI, [0118](0118-the-journeys-run-in-the-worktree-of-the-agent-not-in-the-ci.md) and [0121](0121-the-journeys-run-apart-from-make-ci-and-stop-at-the-first-failure.md); for the check `ci` passing on an up-to-date branch of `dev`, [0057](0057-continuous-integration-around-dev.md)
- Implemented by: `.github/workflows/ci.yml` (`merge_group`, the job `e2e`), `Makefile` (`check-e2e` in the CI, `logs-save`), `apps/front/playwright.config.ts` (the video in the CI), the ruleset `dev` on GitHub

## Context

The journeys left the CI (0118): 37 journeys and a stack recreated took 12 minutes, where the CI took 2. Each developer ran them in their worktree instead. 0118 named its price: nothing on GitHub proves that they ran. Since then, the base set has only 26 + 1 + 1 journeys (0122), and they give the running stack the demonstration back instead of recreating everything (0205): some 7 minutes from nothing.

On the machine, the journeys and the mutations of several developers share 12 processors. On the night of November 25, four developers worked at once. The journeys waited 4 to 15 minutes for the machine's lock, behind mutations. The kernel twice killed a process of a bounded container ("Memory cgroup out of memory"), and one journey failed because of it. A runner's MiniStack has no bound (0118).

`dev`'s ruleset required the check `ci` on a branch up to date with `dev` (`strict`), and a merge by rebase. Each merge put every other pull request behind. The maintainer brought them up to date one at a time ("Update branch"), and each update ran the CI again. Journeys on every pull request would have added some 10 minutes to each of these runs.

GitHub's merge queue rebases a pull request onto `dev` and onto the pull requests queued before it. It runs the CI once on that result (the event `merge_group`), then merges it by rebase. The history stays linear.

## Decision

- A pull request to `dev` is merged through the merge queue, by rebase. `dev`'s ruleset requires the checks `ci` and `e2e` in the queue, and no longer requires a branch up to date with `dev`.
- The CI listens to `merge_group`. The queue runs the same checks as a pull request, on the changes of its group since the `dev` it starts from (`make changed-checks`).
- The queue mutates nothing: the pull request's CI mutated its added lines already (0219).
- The queue alone runs the journeys, in the job `e2e`, when its changes need them (`make/changed-checks.sh`). The job starts the stack on the runner, deploys it, gives it the demonstration and runs every project of the journeys (`make check-e2e`). It runs beside the other checks. A group whose changes need no journey passes the job at once, without a stack, so that the queue does not wait for it.
- A push to a pull request, to `dev` or to `main` runs no journey.
- When a journey fails, the pull request leaves the queue. The job keeps Playwright's report (traces, screenshots and the video of each journey that failed) in the artifact `e2e-report`, and the stack's logs (its services and MiniStack's functions: the API, the storage, the relay, the worker) in `e2e-logs`.
- `make check-e2e` and `make e2e` still run in a worktree and on the stack of the main checkout. `check-e2e` runs in the CI too, never in the main checkout.
- `main` keeps its pull requests from `dev`, merged by a merge commit (0058): it has no queue.

## Consequences

- Nobody brings a pull request up to date with `dev` by hand any more. Two pull requests queued one after the other are each checked on the code that reaches `dev`.
- GitHub proves that the journeys ran on what reaches `dev`, on a runner with an unbounded MiniStack.
- A pull request takes some 10 more minutes between its addition to the queue and its merge, when its changes need the journeys.
- A journey broken by a change is caught at the merge, after the review. The developer can still run the journeys in their worktree before, to catch it sooner.
- A pull request's CI no longer proves the branch on the last `dev`: the queue does, before the merge.
- The runner starts the stack from nothing for each group: it builds the images and deploys the functions each time.

## Alternatives considered

- The journeys on every pull request: some 10 minutes on each push and on each update of the branch. The maintainer set it aside.
- The journeys after the merge only (the pushes of `dev`): a broken journey would reach `dev` first. It was proposed for 0118 and set aside then.
- The journeys kept in the worktree, under the machine's lock: the waits and the bounded containers of November 25.

## Revisit when

The job `e2e` makes the queue wait more than the pull requests it serializes, or a journey fails on the runner and not in a worktree.
