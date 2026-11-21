# prospecter/core

The application's hexagon: the bounded contexts Accounts, PersonalData, Prospecting, Reporting, Connectors, Campaigns and Booking and their shared kernel, in pure PHP. It is an internal Composer package of the monorepo, not published (`docs/architecture/monorepo.md`).

## What is in force

Read before working on the core: `docs/architecture/core.md` (the hexagon's layout and rules), `docs/architecture/contexts.md` (what the contexts publish to each other), `docs/domain/<context>.md` (a context's language, rules and features, and where they live in the code), `docs/architecture/tests.md` (the scenarios and the doubles).

## Commands

Run them from the repository root (the core has its own `vendor/`, installed by `make vendor`):

- `make test-core`: the Behat scenarios (`features/`, one feature per intent or close to it: a feature may hold a sibling intent, as `rattacher-a-une-holding` holds the detachment), then PHPUnit (`tests/Domain`, `tests/Application`, `tests/Architecture`, and `tests/PHPStan`: the rules of `packages/phpstan-rules`, on their fixtures in `packages/phpstan-rules/fixtures`).
- `make mutation`: mutates the lines the branch adds in `src`, kills the mutants with the unit tests and with the scenarios PHPUnit runs (`tests/Mutation`, ADR 0091), and lists the survivors.
- `make lint` / `make fix` cover the core too (PHPStan level 5, php-cs-fixer with strict types, Rector). PHPStan refuses a test that reads the real clock (ADR 0150): a test names its moment.
- `make test-core CLOCK_SHIFT=+365d`: the same, one year later.
- A single command: `docker compose run --rm --no-deps -w /var/www/src/packages/core php vendor/bin/behat features/demenager.feature`.

## History

Imported from the `develop` branch of `SO-LEAN/cleanprospecter` (2021, first-parent history replayed). That repository still has `claude/refactor-ddd-architecture-UJMig`, an unmerged DDD refactoring.
