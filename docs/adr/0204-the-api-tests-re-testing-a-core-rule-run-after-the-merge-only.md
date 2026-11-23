# 0204. The API tests re-testing a core rule run after the merge only

- Date: 2026-11-23
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/Makefile` (`test`, `EXCLUDED_GROUPS`), `.github/workflows/ci.yml` (`ALL` on a push), `apps/api/tests/Architecture/CoreRuleGroupDoesNotGrowTest.php`, the `core-rule` group on 33 tests of `apps/api/tests/Api`, `docs/architecture/tests.md`, `workflow.md`, `commands.md`

## Context

The API had some 1,865 tests. Each one sends its HTTP request to DynamoDB and Cognito on MiniStack, and its check lasted some two and a half minutes in the CI. Some of these tests re-test a rule that the core's scenarios already prove, and add only the refusal's translation to HTTP. Each new rule was written twice, so the API tests grew with the core.

## Decision

- The API tests test the API's adaptation of the core: HTTP, MCP, persistence, Cognito, the messaging, the storage and the rights. A core rule is tested in the core.
- An API test that re-tests a rule proven by a core scenario or unit test, and adds only its HTTP translation, carries `#[Group('core-rule')]` on its method. Out of the group stay, for each endpoint and each MCP tool: one nominal case, and one refusal for each kind of translation (validation, business rule, conflict, not found). The rights, the adapters and any test in doubt also stay out.
- A branch's check leaves the group out: `make test-api`, `make check-api`, `make ci` and a pull request's CI. A push to `dev` or `main` runs it (`ALL=1`), and a grouped test that fails makes that push's CI red. `ALL=1` and a test named by `FILTER` run it locally.
- The group does not grow. No new test enters it, because a new rule is tested in the core. A test of the group that breaks is removed when the core proves its rule, and repaired otherwise. `CoreRuleGroupDoesNotGrowTest` holds the group's size and refuses the group on a class.
- This completes 0122 for the API: the journeys keep only what no other test links, and the API tests keep only what the core does not prove.

## Consequences

- 33 tests in the group: the gain per pull request is small, because the criterion keeps every test in doubt out. The pull request measures it.
- A grouped test broken by a branch is found after its merge, on `dev`, and no longer before it.
- `make mutation` still runs every test that covers the mutated lines, the group included.

## Alternatives considered

- Removing these tests: the maintainer chose to keep them in a group that runs after each merge (ticket of 23/11).
- Testing the voter by unit tests instead of the role matrix over HTTP, and making the MCP tool tests generic: left for later, if the time becomes a problem again.

## Revisit when

A grouped test fails on `dev` for a defect that no core scenario catches, or the API's check grows past three minutes again.
