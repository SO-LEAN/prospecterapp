# prospecter/core

The hexagon of the application: the bounded contexts Accounts, PersonalData and Prospecting and their shared kernel, in pure PHP. Internal Composer package of the monorepo, not published (`docs/architecture/monorepo.md`).

## What is in force

Read before working on it: `docs/architecture/core.md` (layout and rules of the hexagon), `docs/architecture/contexts.md` (what the contexts publish to each other), `docs/domain/<context>.md` (the language, the rules and the features of a context, and where they are in the code), `docs/architecture/tests.md` (the scenarios and the doubles).

## Commands

Run from the repository root (the core has its own `vendor/`, installed by `make vendor`):

- `make test-core`: the Behat scenarios (`features/`, one per intent or close: a feature may hold a sibling intent, `rattacher-a-une-holding` holds the detachment), then PHPUnit (`tests/Domain`, `tests/Application`, `tests/Architecture`)
- `make lint` / `make fix` cover the core too (PHPStan level 5, php-cs-fixer with strict types, Rector)
- one command: `docker compose run --rm --no-deps -w /var/www/src/packages/core php vendor/bin/behat features/demenager.feature`

## History

Imported from `SO-LEAN/cleanprospecter` branch `develop` (2021, first-parent history replayed). That repository still has `claude/refactor-ddd-architecture-UJMig`, an unmerged DDD refactoring.
