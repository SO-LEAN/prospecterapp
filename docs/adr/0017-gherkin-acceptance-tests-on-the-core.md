# 0017. Gherkin acceptance tests on the core, only ports doubled

- Date: 2026-10-15
- Status: Accepted
- Implemented by: c6f774e, ee256db, ec09c80; `packages/core/features`, `packages/core/tests`, `docs/core-tests.md`

## Context

The core was tested from the API with PHPUnit and Prophecy: every collaborator mocked, calls verified, getters and setters tested by reflection. These tests described the implementation, not the rules, broke at each refactoring and missed real gaps (0019).

## Decision

- Acceptance tests at the entrance of the hexagon: Behat scenarios in French Gherkin (`# language: fr`), one feature per use case, written before the rule (ATDD).
- Nothing inside the hexagon is doubled: entities, value objects, use cases and internal services are the real classes. Only ports get working in-memory implementations (`tests/Double`), behaving like their adapters (unique email, copies stored like a database, real rollback, file moved by the storage). No mocking library.
- Scenarios call the use cases through `tests/Acceptance/Application`, not through `UseCasesFacade` (see 0020).
- PHPUnit only where a scenario cannot go (today the facade's roles), testing behaviour, never structure. Double loop: ATDD outside, TDD inside for pure logic rich in edge cases.
- The core has its own tooling (`composer.json` dev dependencies, `make test-core`, its lint).

## Consequences

- The scenarios state the business rules and survived the modernization (0018) unchanged.
- Gaps were found by writing them (ownership, missing logo, forgotten saves).
- The doubles must follow the real adapters (search, ordering, uniqueness): a difference makes a scenario lie.

## Alternatives considered

- For the existing Prophecy tests of the use cases: replace them (chosen), convert them to the in-memory doubles, or keep them next to the scenarios.
- After the double loop was explained: keeping use case unit tests besides the scenarios was turned down as redundant; testing entities' getters and setters was turned down as testing structure.
- Gherkin in English, like the code and the commits: French was chosen, the business speaks French.

## Revisit when

Scenarios get slow or hard to read; a port's behaviour can no longer be reproduced in memory (then test the adapter against the real infrastructure).
