# 0106. A rebuild abandons the version being built before emptying it

- Date: 2026-11-12
- Type: architecture
- Status: Accepted
- Supersedes: for the start of a rebuild, [0103](0103-the-reporting-context-and-its-rebuildable-projections.md) and [0104](0104-reporting-keeps-its-figures-by-additions-its-rebuild-run-from-the-console.md)
- Implemented by: `packages/core/src/Reporting/Application/RebuildFigures/RebuildFiguresHandler.php`, `packages/core/src/Reporting/Application/Port/ProjectionVersions.php`, `apps/api/src/Reporting/Persistence/DynamoProjectionVersions.php`, `packages/core/features/recalculer-les-chiffres.feature`

## Context

A rebuild cancelled by a conflict starts again in the same version (ADR 0104), whose `BUILDING` is still there: the worker keeps recording in it. `startBuilding()` emptied every version but the active one by a scan then deletions: a fact written between the two stayed, its addition to `TOTAL` deleted, and the rebuild, finding the fact known, ignored it; the figure stayed one too low until the next rebuild.

## Decision

A rebuild starts in this order, each step a unit of work of its own, in the core (`RebuildFiguresHandler`):

1. it abandons the version being built (`ProjectionVersions::abandonBuilding()`, `BUILDING` removed): the facts received are no longer recorded in it;
2. it waits for the recordings in flight (`RecordingsInFlight::awaitTheirEnd()`, a minute): none that saw that version live writes in it afterwards;
3. it removes every version but the active one (`removeAbandoned()`): nothing writes in them any more;
4. it starts building its version (`startBuilding()`, which no longer removes anything) and waits again, as before.

## Consequences

- No recording writes in a version while it is emptied: a fact received during the start of a rebuild is recorded in the active version, then read again by the rebuild.
- A rebuild waits two minutes before reading the facts again, every account (`app:reporting:rebuild`, `make db`).
- Each retry of a conflict waits the two minutes again.

## Alternatives considered

- A retry in a version of its own, after the one abandoned: the count would be right without changing the order, but what the worker wrote late in the abandoned version would stay until the next rebuild. Kept the next number after the active one, the order emptying the abandoned version for good.

## Revisit when

A rebuild must start without waiting, or the worker runs longer than the wait.
