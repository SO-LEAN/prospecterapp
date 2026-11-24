# 0208. A new API test proves only the adaptation, and each kind of refusal is translated once

- Date: 2026-11-24
- Type: architecture
- Status: Accepted
- Supersedes: for one refusal for each kind of translation per endpoint and per MCP tool, [0204](0204-the-api-tests-re-testing-a-core-rule-run-after-the-merge-only.md)
- Implemented by: `apps/api/tests/Shared/UseCaseExceptionListenerTest.php`, a case of `apps/api/tests/Shared/Mcp/RefusingToolsTest.php`, `docs/architecture/tests.md`, `api.md`, `mcp.md`

## Context

`tests/Api` had 764 test methods, and some 280 of them tested a refusal whose rule a core scenario already proves. ADR 0204 put 33 of them in the `core-rule` group. Its criterion kept one refusal of each kind per endpoint and per MCP tool, so each new endpoint still added its 404, its 422 and its 409. Yet one listener translates every refusal the same way: `Shared\UseCaseExceptionListener` for HTTP and `Shared\Mcp\RefusingTools` for MCP, both through `Shared\RefusalProblems`.

## Decision

- The API tests of a new or changed endpoint or MCP tool prove its adaptation only: the route, the input (its fields reach the command, its constraints), the persistence, the response and its links, and the rights per role. None re-tests a refusal that the core proves, not even one of each kind.
- Each translation of a core refusal is proven once for the whole API, on its listener rather than on an endpoint:
  - `UseCaseExceptionListenerTest`: not found (404), a rule on a field (422 with its violation), a rule on the whole request (422), a conflict (409), a missing right (403), and what the listener leaves to Symfony;
  - `RefusingToolsTest`: a core refusal becomes the tool's error, with its endpoint's problem;
  - the input's constraints are Symfony's translation, proven once by `OrganizationApiTest` (`testInvalidInputIsRejectedWithViolations`, `testUnknownFieldsAreRejectedInsteadOfIgnored`).
- A refusal that the API makes itself is adaptation, and stays tested: the voter's 404 for another account's resource, a role's 403, `changedMeanwhile`, an invalid `revision` or `timeZone`.
- The reviewer of the definition of done counts an added API test that re-tests a core rule as a deviation. The branch removes it, or shows what it proves beyond the core scenario.
- The refusal tests already written stay. One that breaks is removed when the core proves its rule, and repaired otherwise, as for the `core-rule` group. The group, its ceiling and its run after the merge stay as 0204 set them.

## Consequences

- The API tests stop growing with each rule of the core: a new endpoint adds its nominal case, its input and its rights.
- A translation broken for one endpoint only (a refusal caught and rethrown as something else) is no longer caught by that endpoint's tests. The core's scenario still proves the rule, and the listener's test proves the translation.

## Alternatives considered

None: the ticket of 24/11 asked for this rule.

## Revisit when

A refusal reaches a client with the wrong status, or without its `reason`, while the listener's test is green.
