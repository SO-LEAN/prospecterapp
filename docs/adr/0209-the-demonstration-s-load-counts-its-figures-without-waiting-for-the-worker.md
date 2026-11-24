# 0209. The demonstration's load counts its figures without waiting for the worker

- Date: 2026-11-24
- Type: architecture
- Status: Accepted
- Supersedes: for "`make db` runs it after the data of the demonstration", [0104](0104-reporting-keeps-its-figures-by-additions-its-rebuild-run-from-the-console.md); for "the figures are counted beside the journeys running together", [0205](0205-the-journeys-of-a-branch-start-from-the-demonstration-given-back-to-the-running-stack.md)
- Implemented by: `apps/api/src/Reporting/RebuildOfTheFigures.php`, `apps/api/src/Reporting/NoRecordingsInFlight.php`, `apps/api/config/services.yaml`, `apps/api/src/Shared/Command/RestoreDemonstrationCommand.php`, `apps/api/src/Shared/Command/CreateTablesCommand.php`, `Makefile` (`check-e2e-run`), `apps/api/Makefile` (`db`, `restore`)

## Context

`make check-e2e` on a running stack lasted about 6.6 minutes. Counting the figures took 5 of them, 2.5 minutes longer than the journeys running together beside it. Those 5 minutes are the two waits of 150 s that ADR 0106 puts in a rebuild. They keep a recording of the worker, which runs up to 120 s (ADR 0182), from writing in a version after the rebuild empties it.

The demonstration's load empties every table, Reporting's included, so no version is live. The worker's recordings of what the relay then publishes record nothing until the rebuild starts its version. After that, they record facts the rebuild reads from a state already complete. Nothing else writes until the console's gestures and the journeys start. The count ran beside them only to save time.

## Decision

- The commands that load the demonstration, `app:demo:restore` and `app:tables:recreate --demo`, count its figures at their end, before any gesture. Their first attempt waits for no recording in flight: `RebuildFiguresHandler` is wired for them with `App\Reporting\NoRecordingsInFlight`.
- A step cancelled by a conflict shows that the worker records in the version being built. So a retry goes through the handler that waits for the worker (`RecordingsOfTheWorker`). ADR 0106 holds entirely.
- `app:reporting:rebuild` waits at each attempt, as before, on an instance and locally.
- No flag and no environment chooses. The command's wiring does: it is the command that knows it is the only one to write.
- `make db`, `make -C apps/api restore` and `make check-e2e` no longer run `app:reporting:rebuild`.

## Consequences

- The figures are counted in a few seconds. `make check-e2e` no longer waits for them after the journeys running together.
- A conflict during the demonstration's count costs the 5 minutes again. The pull request that brings this record measures how often.

## Alternatives considered

- A retry without waiting either: the count would always take seconds, but a recording that saw the abandoned version live could write in it between its scan and its deletion, and leave a figure too low. The ticket's architect weighed it. It lost because it breaks ADR 0106.
- The restore waiting for the worker's queue to empty before counting: no conflict at all, at the cost of a port reading the queue and a wait of unknown length. It is kept for later, if conflicts prove frequent.

## Revisit when

A conflict makes the demonstration's count wait 5 minutes in many runs of the journeys, or something else writes while the demonstration loads.
