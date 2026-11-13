# 0110. The integration runs the checks the changes need, the lint and the tests of a project together

- Date: 2026-11-13
- Type: architecture
- Status: Accepted
- Supersedes: for a pull request and `make ci` running every check, [0109](0109-the-checks-run-together-the-tests-on-a-ministack-of-their-own.md)
- Implemented by: `make/changed-checks.sh`, `Makefile` (`ci`, `check-<check>`, `changed-checks`), `make/php.mk`, `.github/workflows/ci.yml`

## Context

Once its checks ran together (0109), the integration lasted its longest check, the API, whose job still ran its PHPStan, its php-cs-fixer, its Rector, then started MiniStack and ran its tests, one after the other; and a change of the front or of the documentation ran the checks of the PHP projects all the same. The user asked to run the integration on the projects a change touches only, and to parallelise the commands each check ran one after the other.

## Decision

- `make/changed-checks.sh` maps the changed files to the checks they need: a project checks itself when its files change, the API and the contract when the core does, the contract when the API or the front does; the Terraform when an infrastructure changes; a file every check depends on (the Makefiles, the images of the command line, `docker-compose.yml`, the workflow of the CI) or a file of no known project needs them all; the documentation, the stack (nginx, the Lambdas) and the workflows of the deployment need none. The secrets of the branch and the references to the records are always checked.
- A pull request runs the checks its changes since its base need (`make changed-checks`, the job `changes`); a push of `dev` or `main` runs every one. `make ci` runs those of the changes since `origin/dev`, committed or not, untracked files included; `ALL=1` runs every one.
- Each check is a target `check-<check>` whose steps `make -j` runs together: the lint of a PHP project as three targets (`lint-phpstan`, `lint-style`, `lint-rector`, `make/php.mk`) beside its tests, MiniStack starting meanwhile; the lint of the front beside its tests, its build after them (both generate the tree of the routes).

## Consequences

- A change of the front or of the documentation no longer waits for the PHP projects; the job of a check lasts its longest step rather than the sum of its steps.
- A dependency the map does not know is not checked by a pull request: a new project or a new link between projects updates `make/changed-checks.sh`; the push of `dev` runs every check and catches what it missed.
- The output of a check comes per step, once each ends.

## Alternatives considered

None was weighed with the user: the decision answers his request.

## Revisit when

A pull request passes while the push of `dev` it lands as fails: the map misses a dependency.
