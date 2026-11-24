# prospecter/core

The application's hexagon: the bounded contexts Accounts, PersonalData, Prospecting, Reporting, Connectors, Campaigns and Booking and their shared kernel, in pure PHP. It is an internal Composer package of the monorepo and is not published (`docs/architecture/monorepo.md`).

## What is in force

Read before working on the core: `docs/architecture/core.md` (the hexagon's layout and rules), `docs/architecture/contexts.md` (what the contexts publish to each other), `docs/domain/<context>.md` (a context's language, rules and features, and where they live in the code), `docs/architecture/tests.md` (the scenarios and the doubles).

## Commands

Run them from the repository root (the core has its own `vendor/`, installed by `make vendor`):

- `make test-core`: runs the Behat scenarios, then PHPUnit.
  - The scenarios live in `features/`, one feature per intent or close to it. A feature may hold a sibling intent: `rattacher-a-une-holding` holds the detachment.
  - PHPUnit runs `tests/Domain`, `tests/Application`, `tests/Architecture`, `tests/Support` (the tests' helpers, such as `State`) and `tests/PHPStan` (the rules of `packages/phpstan-rules` and the event storming's generator, on their fixtures in `packages/phpstan-rules/fixtures`).
- `make mutation`: mutates the lines the branch adds in `src`. The unit tests and the scenarios that PHPUnit runs (`tests/Mutation`, ADR 0091) kill the mutants. It then lists the survivors, which the definition of done holds to a threshold of 80 % (ADR 0202). It fails when it skips a mutant (ADR 0196).
- `make lint` / `make fix` cover the core too: PHPStan at level `max` with no baseline, php-cs-fixer with strict types, and Rector (ADR 0192). A test reads an aggregate's private state through `StateOf::the()`, typed by its `State`, and reads what a scenario expects through `Expect::present()`. PHPStan refuses a test that reads the real clock (ADR 0150): a test names its moment.
- `make test-core CLOCK_SHIFT=+365d`: runs the same tests one year later.
- To run a single command: `docker compose run --rm --no-deps -w /var/www/src/packages/core php vendor/bin/behat features/demenager.feature`.

## History

The core was imported from the `develop` branch of `SO-LEAN/cleanprospecter` (2021), with its first-parent history replayed. That repository still has `claude/refactor-ddd-architecture-UJMig`, an unmerged DDD refactoring.
