# 0091. Tests of mutation measure what the tests of a branch protect: Infection and Stryker on the lines it adds, the survivors listed

- Date: 2026-11-02
- Type: architecture
- Status: Accepted
- Implemented by: `make mutation`, `make mutation-api`, `make/mutation.mk`, `packages/core/tests/Mutation`, the `infection.json5` of the core and the API, `apps/front/stryker.config.json`, `apps/api/.env.test`, `docs/architecture/tests.md`

## Context

That a scenario protects its rule was declared ("I broke the rule and the scenario failed"), never verified, and regressions went through. A mutation test changes the code (a mutant) and runs the tests: a mutant they let survive is a change they do not see. The business rules of the core are Behat scenarios, which Infection, the mutation tool of PHP, does not know: it knows PHPUnit, and the code only the scenarios cover (the use cases) is not mutated at all, without a word. The API is tested by PHPUnit on MiniStack, the front by Vitest. A spike measured the ways on a recent branch of medium size.

## Decision

- **Only the lines a branch adds since `origin/dev`** are mutated, committed or not, a new file once added to git (`BASE=` another reference): Infection with `--git-diff-lines`, Stryker with the ranges of the added lines (`--mutate <file>:<first>-<last>`), which `make/mutation.mk` reads in `git diff -U0`. A branch adding no line runs nothing.
- **The core: Infection on PHPUnit, the scenarios included.** `tests/Mutation/ScenariosTest` runs the scenarios of each feature in its process, as a PHPUnit test whose coverage PHPUnit records; `tests/Mutation/phpunit.xml.dist` gathers it with the unit tests, `make test-core` runs neither twice.
- **The front: Stryker with Vitest**, per test coverage, without the tests, the generated files and the components of shadcn.
- **The API: Infection on PHPUnit, on demand** (`make mutation-api`, on MiniStack): each process of Infection has its own tables and user pool (`TEST_TOKEN` in `.env.test`).
- **No minimum score, out of `make ci` and of the CI**: the targets list the mutants that survive, with their diff; each is killed by a test, or said equivalent or noise in the pull request.

## Consequences

- `make mutation` (the core, then the front) says what the tests of a branch let pass, in minutes: the first run of every test, with the coverage, is most of it, whatever the size of the branch (about 2 min for the core under Xdebug, 4 min for the front).
- The API costs minutes per mutant: a line most tests cross (signing in, the anticorruption layers) is skipped by Infection past its timeout, and the run of a branch of medium size takes about 13 minutes.
- Infection and Stryker are dev dependencies of their projects; the scenarios run by PHPUnit depend on the console API of Behat (`ApplicationFactory`, `--xdebug`, its error handlers), checked at each upgrade of Behat.
- A mutant may be equivalent (a cast without effect): a score would punish it, a list lets it be justified.

## Alternatives considered

- Infection on PHPUnit alone for the core: 19 of the 41 mutants of the branch measured, the use cases invisible; rejected.
- A test per scenario rather than per feature: Behat booted 172 times under Xdebug, the first run takes 6 minutes and Infection skips every mutant past its timeout; rejected.
- An adapter of Infection for Behat (`infection/abstract-testframework-adapter`, the coverage of each scenario by `dvdoug/behat-code-coverage`): twice as fast and precise per scenario, but one framework per run leaves the unit tests out (two runs whose reports would be merged), and its contract is the one Infection is replacing; kept as the way to take if the time of the core matters.
- A minimum score in `make ci`: ten minutes more on each branch, and a threshold arbitrary while equivalent mutants count; rejected.

## Revisit when

- The time of `make mutation` keeps a branch from being checked before its pull request: the adapter for Behat, or a non blocking job of the CI.
- Infection learns Behat, or Stryker reads a git diff.
