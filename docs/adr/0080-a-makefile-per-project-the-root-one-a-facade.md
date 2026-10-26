# 0080. A Makefile per project, the root one a façade

- Date: 2026-10-23
- Status: Accepted, applied to the scripts of `tools/` by [0083](0083-the-scripts-of-tools-become-recipes-of-their-project.md)
- Implemented by: `Makefile`, `make/*.mk`, `packages/core/Makefile`, `apps/{api,storage,front}/Makefile`, `tools/ci/code-version`, `tools/package/lambda`

## Context

The root `Makefile` (ADR 0015) held every command of every project, its Docker calls, the Terraform of the apps and what crosses the projects: one file of 217 lines, where what a project can do is not seen from its directory. The Terraform workflows find what an app can do by `make -n package-<app>` and `make -n release-<app>` at the root (ADR 0063, 0066), and the version of the code of an app is a digest of all its tracked files but its infrastructure and its tests (ADR 0063).

## Decision

- Each project (`packages/core`, `apps/api`, `apps/storage`, `apps/front`) has its Makefile, run from its directory, with the names of the project: `vendor` (`install` for the front), `test`, `lint`, `fix`, and what is its own (`db`, `console`, `openapi`, `api-types`, `e2e`, `package`, `release`...); `make` alone lists its commands.
- The Makefiles include only the shared fragments of `make/`: `common.mk` (Docker Compose from any directory, the container working in the directory of the project, the UID/GID, the list of the commands), `aws.mk` (the values of an instance, the AWS CLI, the images of Terraform and tflint), `infra.mk` (`infra-init|plan|apply|lint` of the app of the directory, `INSTANCE=` only).
- The root `Makefile` is the façade: every target keeps its name, its parameters, its help and its effect, and runs the target of the project by `$(MAKE) -C <directory>`. Only what crosses the projects is written there (`install`, `build`, `ci`, the stack, `api-doc`, `contract`, the secrets, the hooks, `infra-lint` of every app). A project never runs the Makefile of another one.
- The Makefile of a project does not change the version of its code: `tools/ci/code-version` and `tools/package/lambda` leave it out, like `infra/` and `tests/`; a change of `make/` plans and applies every app, like the root `Makefile`.
- A script becomes a recipe of the Makefile of its project, unless it runs in an image without make, or a tool requires a file (a git hook, the entrypoint of nginx, `docker/lambda/deploy.sh` copied in the image `lambda-deploy`).

## Consequences

- What a project can do is read in its directory; the root lists the same commands as before.
- A root target passes its command-line variables on to the project (`CMD`, `INSTANCE`, `CLEAN`, `INFRA_*_FLAGS`): an `APP` given to the root is the directory, the project knows its app by its own.
- `make -C <directory>` prints the directories it enters; run from the root, the façade does not.

## Alternatives considered

None were weighed: the shape was proposed with the need.

## Revisit when

A project leaves the monorepo, or a tool other than make drives the projects.
