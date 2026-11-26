# 0219. A pull request mutates its added lines in its CI, a job per part, beside the checks

- Date: 2026-11-26
- Type: architecture
- Status: Accepted
- Supersedes: for "mutation testing stays out of make ci and of the CI", [0202](0202-the-definition-of-done-measures-the-mutation-of-the-added-lines-against-a-threshold.md)
- Implemented by: `.github/workflows/ci.yml` (the `mutation` job), `make/changed-checks.sh`, `Makefile` (`mutation-core`, `mutation-front`, `mutation-event-storming`, `mutation-summary`, `mutation-artifact`), `apps/front/Makefile`, `packages/core/infection.json5`, `apps/front/mutation/`, `docs/architecture/workflow.md`, `docs/architecture/tests.md`, `docs/architecture/commands.md`

## Context

ADR 0202 made the definition of done run `make mutation` once per branch, on the machine, under the lock it shares with the journeys: one heavy check at a time, mutation or journeys. With four branches developed at once, a mutation of the core waited for that lock up to 54 minutes, and journeys waited 4 to 15 minutes behind mutations. The machine's load reached 11 for 12 processors. A clean mutation takes some 6 minutes for the core, and 3 to 11 minutes for the front and the event storming's generator.

The mutation needs no stack: PHP or Node and the code. The CI of a pull request already has these images, and runs its checks in jobs of their own from the files the pull request changes (ADR 0110). The journeys stay on the machine (ADR 0118).

## Decision

- **The CI of a pull request mutates the lines it adds**, in a `mutation <part>` job per part of the code it adds lines to: `core` (Infection, the core and its event storming analysis), `front` (Stryker, the SPA), `event-storming` (Stryker, the event storming's generator, ADR 0218). `make changed-checks` names these parts as `mutation-<part>`, from the files each tool mutates. A part the pull request does not touch gets no job. A push to `dev` or `main` mutates nothing.
- Each job runs `make mutation-<part> BASE=origin/<base>`: it mutates the lines added since the merge-base of the pull request's merge commit and its base, as `make mutation` does on the machine.
- **Each job publishes the artifact `mutation-<part>`**: the tool's JSON report and `summary.md`, which the job's summary on GitHub shows too. The summary gives the score, the killed, surviving, not covered, timed out and skipped mutants, and each survivor and each mutant no test covers, by its file, its line and its mutator. One summary reads both tools' reports (`apps/front/mutation/summary.ts`).
- **The job fails as `make mutation` does**: when the tool cannot run, or when the core skips a mutant (ADR 0196). A score under the threshold does not fail it: the definition of done keeps judging the threshold of 80 % per project (ADR 0202).
- **The mutation jobs are no part of the `ci` check** that the rulesets require, and `make ci` runs no mutation.
- `make mutation` keeps working on the machine, for a developer who wants to run it.

## Consequences

- The mutations no longer take the machine's lock or its processors, and run while the journeys run on the machine.
- A pull request touching the code costs one to three more jobs of the CI, some 5 to 15 minutes each, beside the checks. The merge does not wait for them: the `ci` check does not need them.
- How the definition of done reads the artifact instead of running `make mutation` is left to its own tooling, outside this repository.
- The summary lives with the front's tools: its tests run with them, and the core's job builds the Node image to write it.

## Alternatives considered

- The mutation jobs among the checks the `ci` job sums up, weighed with this change: a mutation would then hold every merge by some minutes, though the definition of done judges its result, not the CI. The ticket keeps the threshold with the definition of done, so the jobs stay beside the `ci` check.

## Revisit when

- The mutation jobs make the CI's minutes or its queue a constraint.
- The definition of done reads the artifacts: `make mutation` on the machine may then go.
