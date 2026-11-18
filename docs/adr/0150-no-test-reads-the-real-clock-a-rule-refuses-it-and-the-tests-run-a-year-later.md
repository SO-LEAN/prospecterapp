# 0150. No test reads the real clock: a rule refuses it, and the tests run a year later

- Date: 2026-11-18
- Type: architecture
- Status: Accepted
- Implemented by: `packages/phpstan-rules/TestsReadNoRealClockRule.php`, `apps/front/lint/real-clock.grit`, `apps/front/src/test/setup.ts`, `apps/api/config/services.yaml` (`when@test`), `make/common.mk` (`CLOCK_SHIFT`), `docker/php`, `docker/node`

## Context
A test of the front set a planned exchange at a fixed moment without fixing its clock: green the day it was written, red once the day passed, the screen no longer offering to move it. The scenarios of the core and the controllers of the API already ran on a fixed moment, but many unit tests, tests of the API and tests of the front read the real one, and nothing kept a new test from doing so. Biome's GritQL matches nodes, not a file, so a lint refusing "a file naming a moment without `vi.setSystemTime`" cannot be written in it. MiniStack forgets the items of DynamoDB on the real clock, and its Cognito issues the tokens on it.

## Decision
- A test gives what it tests a fixed moment. `make lint` refuses a read of the real clock in a test: PHPStan in the core, the API and the storage (`new DateTimeImmutable()` without a moment or with one relative to now, `time()`, `microtime()`, `date()` without a timestamp, `NativeClock`, `MockClock` without a moment, `Clock::get()`), Biome in the tests and the journeys of the front (`Date.now()`, `new Date()`). An exception is named where it is made, with its reason.
- The front fixes the date of every Vitest test in `setup.ts` (`THE_MOMENT_OF_THE_TESTS`); a test moves it by `vi.setSystemTime`.
- In the tests of the API, what the tables forget is counted from `app.commit_clock`, a `MockClock` in 2100, long after any run, so that MiniStack forgets nothing a test reads. The clock checking the tokens stays the real one: the one exception.
- `make test CLOCK_SHIFT=+365d` runs the tests of the core, the storage and the front on the clock of the system moved by libfaketime (installed in the node image, built from its pinned source in the PHP image, Amazon Linux having no package of it). The API stays on the real clock: its tests sign in on the Cognito of MiniStack.

## Consequences
- A test green today stays green tomorrow; the journeys read the clock only to name what they create (`e2e/unique.ts`).
- A test of the front naming a future moment without moving the clock passes, against the moment of the tests: the lint refuses only the reads of the real clock, not a moment named.
- The API is not run a year later; its guard is the PHPStan rule and its fixed clocks. The moment of the tables of its tests becomes the past in 2100.
- The image of PHP downloads the source of libfaketime at its build, checked by its digest.

## Alternatives considered
- A lint refusing a test file naming a moment without `vi.setSystemTime` (the QA journey of the ticket): Biome cannot express it, and a script outside Biome would refuse a moment that is only data; the maintainer chose the date fixed in `setup.ts`.
- Running the API a year later too, with libfaketime in the MiniStack of the tests (an image we do not build) or a clock of the tokens accepting those issued on the real one: the maintainer kept the API on the real clock.

## Revisit when
MiniStack lets its clock be moved, or Biome lints a whole file.
