# 0221. The CI splits the API's check into jobs, and caches the images and the tools' results

- Date: 2026-11-26
- Type: architecture
- Status: Accepted
- Supersedes: for "the lint and the tests of a project together" in the CI's job of the API, [0110](0110-the-integration-runs-the-checks-the-changes-need.md)
- Implemented by: `.github/workflows/ci.yml` (the `changes` and `check` jobs), `Makefile` (`check-api-lint`, `check-api-tests-<i>-of-<n>`, `images-env`), `apps/api/Makefile` (`SHARD`), `docs/architecture/workflow.md`, `docs/architecture/commands.md`

## Context

A pull request's CI took some 3 min 40 s when it ran every check, and 5 min 30 s on a push to `dev`, which runs the API tests that re-test a core rule too. The checks run in parallel jobs (ADR 0110), so the slowest job sets the CI's length: `check api`, 3 min 20 s on a pull request. Its tests already run in four processes, as many as a standard runner has processors. Its lint ran beside them, on the same processors.

Every job that needed the PHP or the Node image built it again, some 30 s for the PHP image. The Composer and pnpm dependencies were already cached. PHPStan's result cache and Vitest's results started empty in each job.

The repository is public: the standard runners and the cache of GitHub Actions (10 GB) cost nothing. Bigger runners would.

## Decision

- **The API's check runs in four jobs of the CI**: `api-lint` (PHPStan, php-cs-fixer, Rector) and `api-tests-<i>-of-3`, each the i-th share of the test files (`paratest --shard`, the files dealt in turn), in four processes. The `changes` job expands the check `api` that `make changed-checks` names into these jobs, the number of shares set once in the workflow (`API_TEST_JOBS`). The `ci` job passes only when all of them pass.
- `make check-api` and `make ci` still run the API's lint and tests together on the machine.
- **The images of the command line come from the cache of Buildx** for GitHub Actions (`type=gha`), one scope per image, under the tags make expects (`make images-env`). An image is rebuilt only when its `docker/` directory changes.
- **The tools' caches are kept from one run to the next**: PHPStan's result cache of the core, the API, the storage and the event storming's analysis, and Vitest's results. Each key starts with a digest of the tool's configuration and the lock files. Each run saves its own cache and restores the latest one of the same configuration.

## Consequences

- An API change costs three more jobs per run. The runners are free, and the jobs run in parallel.
- Each run saves a few PHPStan and Vitest caches. GitHub evicts the caches least used once the repository's 10 GB are reached: the dependencies' caches, read by every run, stay.
- The shares are files dealt in turn, not timed: a share holding the slowest files ends last. More shares than three would add jobs whose setup outweighs what they save.
- The mutation jobs and the journeys still build their images; the same cache can serve them.

## Alternatives considered

- An image published on GitHub's registry, named by the ticket beside the cache of Buildx: it needs a token that writes packages and a publishing step, for a gain the cache gives already.
- Two shares instead of three, left to the measure by the ticket: with two, a share's tests take more than a minute longer than the other jobs.

## Revisit when

- Another job than the API's tests sets the CI's length by more than a few seconds.
- The cache of GitHub Actions evicts the dependencies' caches.
