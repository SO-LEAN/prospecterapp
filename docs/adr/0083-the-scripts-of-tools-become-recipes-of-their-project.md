# 0083. The scripts of `tools/` become recipes of their project; the PHPStan rules a package

- Date: 2026-10-26
- Status: Accepted
- Implemented by: `make/aws.mk` (`code-version`, `requires-core`), `make/lambda.mk`, the `changed-apps` and `hooks` targets of the `Makefile`, `apps/{api,storage}/Makefile`, `apps/front/Makefile`, `apps/front/script/release`, `.githooks/pre-commit`, `packages/phpstan-rules/`, `docker/php/Dockerfile`, the workflows `terraform-*.yml`, `docs/monorepo.md`

## Context

ADR 0080 made a script a recipe of the Makefile of its project, unless it runs in an image without make or a tool requires a file. `tools/` still held the scripts of before: the version of the code of an app and the apps a git range changes (ADR 0063), the package of a PHP app for its Lambda (ADR 0063, 0065), the release of the front (ADR 0066), the pre-commit hook (ADR 0059) and the PHPStan rules (ADR 0044), which are no script. A recipe runs a shell per line, and the package runs in the php container: a container per line is slow, and a whole script quoted for `sh -c` is unreadable.

## Decision

- The version of the code of an app is the function `code-version` of `make/aws.mk`, the same digest as before; `make changed-apps RANGE=<range>` at the root prints the apps a range changes, called by the Terraform workflows. A change of a workflow `terraform-*.yml` now plans and applies every app, as its comment always said (the former pattern missed the hyphen of `terraform-apply-*`).
- The package of the api and the storage is the `package` of `make/lambda.mk`, included by their Makefiles: it runs `make lambda-package` of the same Makefile in the php container, which now has make, one recipe line per step.
- The release of the front stays a script, `apps/front/script/release`, run by `make release` of the front: it runs in the image of the AWS CLI, which has no make, and its cleaning is a loop on the releases of the bucket.
- The pre-commit hook is `.githooks/pre-commit` (git runs a file of `core.hooksPath`); the PHPStan rules are `packages/phpstan-rules/`, namespace `Prospecter\PHPStan`, in the `autoload-dev` of the core, the api and the storage. `tools/` is gone; `docs/monorepo.md` lists the scripts left and why.

## Consequences

- `make` is the only entry; a recipe is read in the Makefile of its project.
- A clone where `make hooks` ran before has its hooks path on `tools/git-hooks`, which no longer exists: git then runs no hook until `make hooks` is run again.
- The php image must be rebuilt (`make build`) before `make package-api` or `make package-storage`.
- `apps/front/script/release` is out of the version of the code of the front, like `infra/` and the Makefile: changing it puts no new release.

## Alternatives considered

Running each step of the package in its own container, or the whole release as a make recipe quoted for `sh -c`, were weighed in the ticket and refused: slow, or unreadable.

## Revisit when

The image of the AWS CLI gets make, or the release of the front moves to another tool.
