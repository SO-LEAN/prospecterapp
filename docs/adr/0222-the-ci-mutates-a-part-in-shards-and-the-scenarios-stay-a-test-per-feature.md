# 0222. The CI mutates a part in shards, and the core's scenarios stay a test per feature

- Date: 2026-11-27
- Type: architecture
- Status: Proposed
- Supersedes: for "a job per part", [0219](0219-a-pull-request-mutates-its-added-lines-in-its-ci-a-job-per-part-beside-the-checks.md)
- Implemented by: `.github/workflows/ci.yml` (the `changes`, `mutation` and `mutation-summary` jobs), `make/mutation.mk` (`SHARD`), `packages/core/Makefile`, `apps/front/Makefile`, `Makefile` (`mutation-shards`, `mutation-summary`, `mutation-merge`), `apps/front/mutation/`, `docs/architecture/workflow.md`, `docs/architecture/tests.md`, `docs/architecture/commands.md`

## Context

ADR 0219 mutates a pull request's added lines in its CI, a job per part. The definition of done waits for these jobs. On a branch of the core adding some 1,000 lines, the job `mutation core` took 11 min 30 s: 123 mutants in 10 min 40 s, of which 2 min 20 s for the first run of all the tests under coverage. The job already takes all the processors of its runner, and a standard runner has 4. The repository is public: the standard runners cost nothing, bigger runners would.

Infection does not know Behat. `tests/Mutation/ScenariosTest` runs each feature's scenarios as one PHPUnit test, so a mutant runs every feature that covers its line, each one whole. A finer test, one per scenario, could run fewer scenarios per mutant.

## Decision

- **A part to which a pull request adds many lines is mutated in shards**, each a job on its own runner. The `changes` job asks `make mutation-shards` how many: one per 300 added lines, at most 3, and never more shards than the part's files. A part under 300 lines keeps one job, since each shard pays the first run of all the tests.
- **A shard mutates its share of the part's files** (`SHARD=<i>/<n>`). The files are dealt by the lines the branch adds to them, the biggest first, each to the share holding the fewest lines so far. Stryker is given the ranges of the shard's files. Infection reads the added lines from a diff of the working tree against a base. A shard's base is a tree: the branch's base, with the other shards' files as the branch has them, so only its own files differ.
- **A part mutated in shards ends with one artifact `mutation-<part>`**, as a part's only job does. Each shard publishes `shard-<part>-<i>-of-<n>`. The job `mutation <part> summary` then sums them up: their counts summed, their survivors listed, the score of all their mutants (`make mutation-merge`). A part's only job keeps its tool's own score. A shard that skips a mutant of the core fails, as `make mutation` does.
- **The mutation jobs take their images from the cache**, as the checks do (ADR 0221).
- **The core's scenarios stay one PHPUnit test per feature.** Measured on 1,018 scenarios, under Xdebug: the first run took 13 min 13 s with a test per scenario, against 4 min 20 s with a test per feature. The tests covering a line took no less time, since most scenarios of a feature cross a line that one of them crosses. The features covering a line of `Exchange::planned` took 65 s, and its scenarios 139 s. A line of `AgendaInterlocutors` took 20 s by feature and 36 s by scenario. Each scenario boots Behat again.

## Consequences

- The slowest shard sets the length of a part's mutation. Each shard pays the checkout, the dependencies and the first run of all the tests, some 3 minutes for the core.
- A part in shards costs one more job, the summary, some 30 s after its last shard.
- The shares are dealt by added lines, not by mutants nor by the time their tests take: a shard holding a busy line ends last.
- `make mutation` on the machine runs every part in one go, as before. `SHARD=` runs one share there too.

## Alternatives considered

- A PHPUnit test per scenario, or per group of scenarios, which the ticket asked to measure: see the measure above. A group of scenarios boots Behat as often as it has groups, and the measure shows that a line's covering scenarios are most of its features' scenarios.
- Infection's own filter of files (`--filter`, or paths) cannot be combined with `--git-diff-lines`, which it refuses: the shard's base tree gives the same result.

## Revisit when

A shard of the core takes longer than its first run of the tests, or Infection learns to run Behat's scenarios one by one without booting Behat for each.
