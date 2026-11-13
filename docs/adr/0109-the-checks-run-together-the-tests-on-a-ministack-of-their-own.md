# 0109. The checks of the integration run together, the tests on a MiniStack of their own

- Date: 2026-11-13
- Type: architecture
- Status: Accepted
- Supersedes: for the pushes of the branches of work running the CI, [0057](0057-continuous-integration-around-dev.md)
- Implemented by: `.github/workflows/ci.yml`, `Makefile` (`ci`, `check-<project>`), `make/common.mk` (`ministack-test`), `make/aws.mk` (the cache of the lint of the Terraform), `docker-compose.yml` (`ministack-test`), `apps/api/Makefile` (ParaTest), the `.env.test` of the API and the storage, `apps/front/vite.config.ts` (the timeout of a test)

## Context

The integration had grown to one job running every check after the other, about five minutes on GitHub and twice for each push of a branch with a pull request (the push and the pull request). Locally, `make ci` ran the tests of the API and the storage on the MiniStack of the stack, one per machine: a session waited for the stack another one held before running its checks, and the lint of the Terraform downloaded the AWS provider and the tflint ruleset again on every run, minutes. The user found the integration unmanageable and asked to parallelise it.

## Decision

- The checks are independent once the images, the dependencies and the contract are there: the CI runs each in a job of its own (the secrets and the references to the records, the Terraform, the core, the API, the storage, the front, the contract), together; the job `ci` sums them up, the check the rulesets require. `make ci` runs them together too (`make -j`), each project's lint, tests and build one after the other (`make check-<project>`).
- The CI runs on the pull requests and on the pushes of `dev` and `main`, no longer on the pushes of the branches of work: the pull request already runs it on the same commits merged with their base.
- The tests of the API and the storage run on a MiniStack of their own (`ministack-test`), one per checkout, without port, state or Lambdas: they no longer need the stack.
- The tests of the API run in four processes with ParaTest, each on its own tables and user pool (`TEST_TOKEN`, as the mutation tests already did).
- The lint of the Terraform keeps the providers and rulesets it downloads in `.cache/` of the checkout, out of git; the CI does not cache them, its runners downloading them faster than they restore a cache.

## Consequences

- A run of the CI lasts its longest check, the tests of the API, instead of the sum of the checks; `make ci` the same locally, and several checkouts check at once.
- A branch pushed without a pull request is no longer checked by the CI: `make ci` before pushing, the pull request as soon as it is checked.
- Each job of the CI builds the image it needs and installs its dependencies: more minutes of runners for less waiting.
- The output of `make ci` comes per check, once each ends, not as it runs.
- The tests of the front share the machine with the other checks: a test may take up to 20 s before it times out.

## Alternatives considered

None was weighed with the user: the decision answers the request to parallelise the integration.

## Revisit when

A check of its own becomes the longest by far again (the tests of the API growing beyond what four processes absorb), or the minutes of the runners become a cost.
