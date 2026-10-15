# 0002. One repository: front, API and core, the core as a local Composer package

- Date: 2026-10-13
- Status: Accepted
- Implemented by: 3f0c522, 136a91d, 28dcb0e; `docs/monorepo.md`

## Context

The business library (`cleanprospecter`) lived in its own repository and was published on Packagist; the application required it from there. Every change to a rule meant a release of the library, then an update of the application. Only this application uses the library.

## Decision

- One repository: `apps/front`, `apps/api`, `packages/core` (`prospecter/core`, namespace `Prospecter\Core`, renamed from `cleanprospecter`), with Docker, make and `docs/` at the root. The library history was imported (first-parent).
- The core is no longer published: the API requires it through a Composer `path` repository (`*@dev`), symlinked in development, copied with `COMPOSER_MIRROR_PATH_REPOS=1` for production builds.
- Each app keeps its own package manager (Composer, pnpm); they share only the HTTP contract and the root tooling.

## Consequences

- A rule, its API adapter and its front change in one commit, tested together.
- No versioning of the core: there is only one consumer.
- The production image must be built from the root and drop the core's own dev files (`docs/monorepo.md`).
- Never set `"symlink": true` on the path repository: it would ignore the mirror variable.

## Alternatives considered

- Keep two repositories and Packagist: release overhead for a library with a single consumer.
- Git submodule: same versioning cost, worse tooling.

## Revisit when

The core gets a second consumer that must evolve at its own pace (another application, a published SDK).
