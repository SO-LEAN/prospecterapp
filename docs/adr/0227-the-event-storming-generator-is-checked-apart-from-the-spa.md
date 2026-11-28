# 0227. The event storming's generator is checked apart from the SPA

- Date: 2026-11-28
- Type: architecture
- Status: Accepted
- Supersedes: for the list of Vitest projects, [0210](0210-the-front-tools-tests-run-in-forks.md); for what `make check-event-storming` runs, [0214](0214-every-context-has-its-wall-generated-from-the-whole-code-and-checked-in-the-ci.md)
- Implemented by: `apps/front/vite.config.ts` (`test.projects`), `apps/front/event-storming/tsconfig.json`, `apps/front/Makefile` (`test PROJECTS=`, `lint-event-storming`), `Makefile` (`check-event-storming`, `check-front`), `make/changed-checks.sh`, `apps/front/checks/changed-checks.test.ts`

## Context

A change to the generator or its walls asked for the front's check and the walls' check. The front's check linted and type-checked the whole front, ran every Vitest project, the SPA's 153 test files among them, then built the SPA. The SPA neither imports nor reads the generator or its walls, so none of that could fail on such a change. A rebase that only touched the generator and the walls spent long minutes in `check-front`, locally and in the CI. The script that lists the checks had no test.

## Decision

- Vitest runs three projects. `spa` (`src/`, `lint/`) is unchanged. `tools` (`infra/`, `mutation/`, `checks/`) and `event-storming` (`event-storming/`, with the walls' global setup) both run in the `forks` pool (ADR 0210).
- `make check-event-storming` lints the generator alone: Biome on its folder, and TypeScript with its own `tsconfig.json`. Beside that, it writes every wall again and fails on a difference, as before. Then it runs the `event-storming` project, whose tests read the walls' models.
- `make check-front` runs the `spa` and `tools` projects, then the build. `make test-front` runs every project.
- A change to the generator or its walls needs the walls' check only. A file of the front that both read (`package.json`, its lock file, `vite.config.ts`, `tsconfig.json`, `biome.json`, the front's `Makefile`) needs both checks. The CloudFront function needs the front's check besides Terraform's, since the front's tests run it.
- `apps/front/checks/changed-checks.test.ts` runs `make/changed-checks.sh` in the `tools` project.

## Consequences

- A change to the generator alone runs its 19 test files, its lint and the walls' check, without the SPA's tests or its build.
- The front's check no longer runs the generator's tests. A branch that touches a shared file runs both checks.
- `make ci` still runs the walls' check alone, before the other checks.

## Alternatives considered

- Keeping one `tools` project and having `check-event-storming` filter it by path. The front's check would then still have run the generator's tests: the developer weighed it and chose a project of its own.

## Revisit when

The SPA starts importing the generator or reading its walls, or a file outside `event-storming/` that the generator reads is missing from the shared list.
