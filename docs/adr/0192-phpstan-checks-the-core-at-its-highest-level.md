# 0192. PHPStan checks the core at its highest level

- Date: 2026-11-22
- Type: architecture
- Status: Accepted
- Supersedes: for "PHPStan level 5" and the rejected alternative "PHPStan level 6", [0004](0004-php-8-5-and-symfony-8-1.md); for "PHPStan level 5" in the tooling, [0022](0022-clean-code-conventions.md)
- Implemented by: `packages/core/phpstan.dist.neon`, `packages/core/tests/Double/State.php`, `docs/architecture/clean-code.md`, `packages/core/CLAUDE.md`

## Context

ADR 0004 kept PHPStan at level 5, because level 6 asks for an iterable docblock on every array, the kind of type comment ADR 0022 refuses. That left out what levels 6 to 10 check: missing return types, nullability, and `mixed`. PHPStan 2 can ignore a single identifier and keep everything else a level checks. On the core, level 10 reported 855 errors. 600 of them were `mixed`, most from `StateOf::the()`, which gave the tests an aggregate's private state as `array<string, mixed>`. Only 16 were in `src/`.

## Decision

- The core is checked at `level: max`, with no baseline and no `@phpstan-ignore`. We chose `max` over `10` so that a level added by a future PHPStan is taken at its upgrade, when its new errors are read anyway.
- The only ignored identifier is `missingType.iterableValue` (with `reportUnmatched: false`). An array gets an iterable docblock where its values are read, because the call that reads them needs it, never just because the tool asks (ADR 0022).
- Errors are fixed in code with native checks. `StateOf::the()` returns a `State` that reads each property as the type the reader expects and refuses any other type. A scenario reads what it expects through `Expect::present()`. Library results PHPStan types as `mixed` or nullable (`preg_replace`, `Normalizer`, the INSEE tables) are checked where they are taken.
- The API and the storage stay at level 5 until #232 moves them to the same level, with the same ignored identifier.

## Consequences

- The core and its tests are typed end to end, and a property an aggregate no longer has fails the test that reads it.
- PHPStan on the warm core takes about 2 s, about as long as level 5 did.
- A test reading a private property names its type: `->object('facts', ExchangeFacts::class)`.
- A comparison PHPStan proves always true is removed. Comparing the provider of a connection was one, while Google is the only provider.

## Alternatives considered

- Level 8 only: the ticket's minimum. Levels 9 and 10 added only `mixed`, which a typed `State` removed, so the extra cost was small.
- An `assert()` or `instanceof` at each of the 183 reads of `StateOf`, or an array shape per aggregate in the tests: noise in every scenario, or a copy of each aggregate's private state that goes wrong the day it changes.

## Revisit when

A PHPStan upgrade brings a level whose errors cannot be fixed natively.
