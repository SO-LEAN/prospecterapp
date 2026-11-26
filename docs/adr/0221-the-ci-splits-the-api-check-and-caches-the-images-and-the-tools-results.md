# 0221. The CI splits the API's check into jobs, and caches the images and the tools' results

- Date: 2026-11-26
- Type: architecture
- Status: Accepted
- Supersedes: for "the lint and the tests of a project together" in the CI's job of the API, [0110](0110-the-integration-runs-the-checks-the-changes-need.md)
- Implemented by: `.github/workflows/ci.yml` (the `changes` and `check` jobs), `Makefile` (`check-api-lint`, `check-api-tests-<i>-of-<n>`, `images-env`, `image-load`), `apps/api/Makefile` (`SHARD`), `docs/architecture/workflow.md`, `docs/architecture/commands.md`

## Context

A pull request's CI took some 3 min 40 s when it ran every check, and 5 min 30 s on a push to `dev`, which also runs the API tests that re-test a core rule. The checks run in parallel jobs (ADR 0110), so the slowest job sets the CI's length. That job was `check api`, 3 min 20 s on a pull request. Its tests already run in four processes, as many as a standard runner has processors. Its lint ran beside them, on the same processors.

Every job that needed the PHP or the Node image built it again, some 30 s for the PHP image. The Composer and pnpm dependencies were already cached. The caches of PHPStan, Rector, php-cs-fixer and Vitest started empty in each job: the API's lint took about a minute.

The repository is public: the standard runners and the cache of GitHub Actions (10 GB) cost nothing. Bigger runners would.

## Decision

- **The API's check runs in four jobs of the CI**: `api-lint` (PHPStan, php-cs-fixer, Rector) and `api-tests-<i>-of-3`. Each of the three runs one share of the test files (`paratest --shard`, the files dealt in turn), in four processes. The `changes` job expands the check `api` that `make changed-checks` names into these jobs. The workflow sets the number of shares once (`API_TEST_JOBS`). The `ci` job passes only when all of them pass.
- `make check-api` and `make ci` still run the API's lint and tests together on the machine.
- **Each command-line image is an archive in the cache of GitHub Actions**, keyed by the tag make expects of it (`make images-env`). That tag names the image's `docker/` directory and its user. A job loads the archive (`make image-load`, `docker load`). When the cache does not hold the tag yet, the job builds the image and saves its archive.
- **The tools' caches are kept from one run to the next**: the PHP lint's caches (PHPStan, Rector, php-cs-fixer) in the core, the API and the storage, PHPStan's result cache of the event storming's analysis, and Vitest's results. Each key starts with a digest of the tool's configuration and the lock files. Each run saves its own cache and restores the latest one of the same configuration.

## Consequences

- With warm caches, a PHP job gets its image in 6 to 13 s instead of some 30 s, and the API's lint takes 3 s instead of a minute.
- The front's check now sets the CI's length, some 2 minutes: Vitest alone takes about 80 s, and over 130 s on some runners. Splitting it is the next lever.
- An API change costs three more jobs per run. The runners are free, and the jobs run in parallel.
- Each run saves a few of the tools' caches. A change of a `docker/` directory saves a new image archive, some 310 MB for PHP and 100 MB for Node. GitHub evicts the caches least used once the repository's 10 GB are reached. The dependencies' caches and the current archives are read by every run, so they stay.
- The shares are files dealt in turn, not timed: a share holding the slowest files ends last.
- The mutation jobs and the journeys still build their images. The same archives can serve them.

## Alternatives considered

- The cache of Buildx for GitHub Actions (`type=gha`), which the ticket named first, was tried on this branch. Every layer came from the cache, but Buildx then sent the image to Docker as a tarball, and Docker imported it: 25 to 35 s per job, as long as building the image.
- Two shares instead of three, left to the measure by the ticket, were tried on this branch: their jobs took 101 and 117 s, against 88 to 96 s with three. Two shares would end about when the front's check does. Three keep a margin for a push to `dev`, which runs more API tests, and for the day the front's check gets faster.
- An image published on GitHub's registry, which the ticket named too, was not tried: it needs a token that writes packages and a publishing step, and pulling it unpacks as many layers as loading the archive.

## Revisit when

- The front's check, or another job than the API's tests, sets the CI's length by more than a few seconds.
- The cache of GitHub Actions evicts the dependencies' caches or the image archives.
