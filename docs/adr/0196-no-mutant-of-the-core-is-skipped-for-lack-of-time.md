# 0196. No mutant of the core is skipped for lack of time, and make mutation fails when one is

- Date: 2026-11-22
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/infection.json5`, `packages/core/Makefile`, `make/mutation.mk`, the root `Makefile`, `docs/architecture/tests.md` ("Mutation testing"), `docs/architecture/commands.md`, `packages/core/CLAUDE.md`

## Context

ADR 0091 runs Infection on the core's unit tests and scenarios, one PHPUnit test per feature (`tests/Mutation/ScenariosTest`). Infection skips a mutant (S, "required more time than configured") when the time its covering tests took in the first run reaches `timeout`, then 120 s. That first run is under Xdebug coverage, and the lines of `Exchange` are crossed by so many features that every mutant on them was skipped: a branch touching `Exchange` had its mutants applied by hand, and the skipped ones passed unnoticed among Infection's metrics.

A mutant's own run is much cheaper than that first run: Infection runs it without Xdebug, the fastest covering tests first, and stops at the first failing one. Measured on a throwaway edit of an `Exchange` line already protected: the whole `tests/Mutation` suite without Xdebug takes 30 s, and the two mutants were killed by a unit test in milliseconds. With a 120 s `timeout` both were skipped (4 min 28 s); with a timeout above the first run, both were killed (2 min 57 s).

## Decision

- **The core's `timeout` (600 s) is above Infection's whole first run** (some 2 to 3 min): no mutant's covering tests can reach it, so none is skipped for lack of time.
- **`make mutation` prints the number of mutants the core skipped, and fails when there is one**, from the summary Infection writes (`logs.summaryJson`, `var/mutation-summary.json`, removed before each run). The root target then stops before the front: the core's run is not finished while some of its lines are judged by no test. The developer raises the `timeout`, or lists the skipped mutants in the pull request and applies them by hand.
- The API keeps its own `timeout` and its skipped mutants (ADR 0091): out of this decision.

## Consequences

- A branch touching `Exchange`, or any aggregate many scenarios cross, gets each mutant judged in the same few minutes as before: most of the time is still the first run.
- A survivor costs at most its covering tests without Xdebug (some 30 s); a mutant looping forever is stopped by Infection's own limit, five times its tests' first-run time, up to the 600 s.
- Failing on a skipped mutant is no minimum score (ADR 0091 refused one): a survivor still only gets listed.

## Alternatives considered

The ticket listed four ways; the plan weighed them:
- The unit tests only for these lines: the use cases' rules, covered by the scenarios alone, would be judged by nothing again, what ADR 0091 refused.
- PCOV instead of Xdebug: a new extension in the images to speed up a first run that is not the cause.
- Only the scenarios crossing the mutated line: one PHPUnit test per scenario, refused by ADR 0091 (a first run of 6 minutes).
- A timeout computed from the number of scenarios: Infection takes no threshold per mutant, and a fixed bound above the first run gives the same result.

## Revisit when

- The core's first run under Xdebug nears 600 s: the bound is raised, or the adapter for Behat named by ADR 0091 is taken.
