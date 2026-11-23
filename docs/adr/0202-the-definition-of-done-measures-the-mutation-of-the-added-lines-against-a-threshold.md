# 0202. The definition of done measures the mutation of the added lines, against a threshold of 80 % per project

- Date: 2026-11-23
- Type: architecture
- Status: Accepted
- Supersedes: for "no minimum score" and for a survivor justified as noise, [0091](0091-mutation-tests-measure-what-the-tests-of-a-branch-protect.md)
- Implemented by: the prospecter plugin's `/prospecter:dod` and its `dod-reviewer` agent (the backlog repository, plugin 0.23.0), `apps/front/stryker.config.json`, `docs/architecture/tests.md` ("Mutation testing"), `docs/architecture/workflow.md`, `docs/architecture/commands.md`, `packages/core/CLAUDE.md`

## Context

ADR 0091 made `make mutation` mutate the lines a branch adds, in the core (Infection) and the front (Stryker). It refused a minimum score: equivalent mutants would make a threshold arbitrary, and a score in `make ci` would cost some ten minutes on each branch. The survivors were to be listed in the pull request.

No step ran it, though: neither the definition of done, nor its reviewer, nor the CI. Measured on three merged tickets, the core scored 85 % and 83 %, the front 73 %, 81 % and 62 % (92 survivors on one ticket). The core's coverage is 100 %: its tests run every line, but no test sees a notable share of the code's changes (boundaries, steps no assertion reads). The run takes 1 to 6 minutes for the core and 2 to 7 minutes for the front, depending on the lines added.

## Decision

- **Mutation testing of the added lines is a step of the definition of done.** The prospecter plugin's `/prospecter:dod` runs `make mutation` on its first pass only, in the background, while its reviewer reads the branch. It runs under the journeys' lock, shared by every checkout on the machine: never alongside the journeys or another mutation run. The definition of done runs `make ci` only after it. A later pass and a rebase run no mutation. `make mutation-api` stays on demand, out of this step.
- **The threshold is 80 % per project, on the added lines.** The core's score is Infection's exact `msi` (`var/mutation-summary.json`), the mutants no test covers counted in. The front's score is the `total` of Stryker's `All files` line.
- **Under the threshold, each survivor is killed or justified.** Killed: a test, committed alone (`test: …`), green first, then red against the mutant applied by hand, then green once the file is restored. The TDD log shows these three runs. Justified: an equivalent mutant, with no effect a member or a caller can observe, in one line of the pull request. The reviewer reads the mutation log itself and judges each justification on the mutant's diff. A mutant that changes something observable (a value returned or shown, a refusal, a boundary, an event recorded) is no equivalent: it stays a failed criterion until a test kills it.
- **At or over the threshold, the survivors are listed in the pull request**, and nothing more.
- **Equivalent mutants no longer make the threshold arbitrary**, ADR 0091's objection. The threshold decides nothing alone: it only triggers the review of the survivors. Each equivalent mutant is accepted on its own justification, judged one by one.
- The pull request's `## Mutation` section gives each project's score, as measured before the kills, or `not measured` and why. Under the threshold, each survivor (its path and line, its mutator) comes with its test or its justification. At or over it, the survivors are only listed.
- Mutation testing stays out of `make ci` and of the CI.

## Consequences

- A ticket's tests are measured on what they check, not only on what they run: a boundary no test reads shows up as a survivor before the pull request.
- Mutation testing takes 5 to 20 minutes in a worktree, mostly while the reviewer reads. The lock makes a mutation run wait for the journeys of other checkouts, and makes them wait for it.
- A project under the threshold costs a test or a justified line per survivor. An equivalent mutant still costs only its justification: the threshold never forces a test for it.
- The front's measure needs Stryker's first test run to finish: in a worktree it nears 5 minutes, Stryker's default limit, so `dryRunTimeoutMinutes` is 15.

## Alternatives considered

- Keep the survivors listed with no threshold, as ADR 0091 decided: nothing ran the mutation, and the measured scores show code run but not checked going through. Rejected by the maintainer.
- A threshold in `make ci` or in the CI, refused by ADR 0091 for its time: still refused. The definition of done runs the mutation once per branch, beside the reviewer.

## Revisit when

- A few tickets have gone through the step: the threshold of 80 % is reviewed against the scores and the survivors they met.
- The first pass's time keeps a branch from its pull request: the adapter for Behat named by ADR 0091, or a non blocking job of the CI.
