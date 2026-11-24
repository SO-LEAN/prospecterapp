# 0211. An MCP tool is tested by what it adds to its endpoint

- Date: 2026-11-24
- Type: architecture
- Status: Accepted
- Supersedes: for the rights per role of an MCP tool that adapts an endpoint, proven by its API tests, [0208](0208-a-new-api-test-proves-only-the-adaptation.md)
- Implemented by: `apps/api/tests/Architecture/EveryToolIsWiredAsItsControllerTest.php`, `apps/api/tests/Architecture/WhatAMethodCalls.php`, `apps/api/tests/Base/ActsAsItsEndpoint.php`, the tools' tests in `apps/api/tests/Api`, `docs/architecture/mcp.md`, `tests.md`

## Context

Every member endpoint has its tool (ADR 0163). The tool does what its controller does, in the same order: it checks the same `#[IsGranted]` attributes through `Security\ToolAccess`, it builds and validates the same input, and it calls the same core reader, query or command. The tools' API tests still replayed their endpoint's cases: each tool was called by five members (a prospector, an assistant, an administrator, a member who is not a prospector, a member of another account), and each refusal was compared with its endpoint's. Each login signs in on MiniStack's Cognito, so a feature was tested twice, once by HTTP and once by the MCP server.

## Decision

- `tests/Architecture/EveryToolIsWiredAsItsControllerTest` reads each tool that adapts an endpoint, and its controller, in their sources (PHP-Parser). It proves that the tool checks its controller's rights: the `#[IsGranted]` attributes, and the rights the controller's code checks. It also proves that the tool calls every reader, query and command its controller calls, through its helpers and its inputs. A list names the calls that a tool makes otherwise, with its record (ADR 0166, 0185).
- A tool's API tests keep one nominal case through the server, as a prospector: its arguments become its endpoint's input, and its answer is its endpoint's. They also prove what its endpoint does not have: a revision that the AI gives, a file downloaded from the web, an answer of its own, a refusal of the server.
- A tool's test that replays a case of its endpoint's tests or of the core is removed: a member's role, another account's resource, a core refusal, its annotations (`EveryEndpointHasItsToolTest` checks those).
- Each refusal the server translates is proven once, on a reading tool: a member's role, another account's resource, an argument's violations and a service out of reach.
- A tool without an endpoint (`identify`, `record-contact`, `get-next-recipient`, `get-me`) is tested like an endpoint, its rights per role included.

## Consequences

- A new tool adds one nominal API test, and tests for what it adds only.
- The wiring test reads the sources, not the behaviour. A tool calling the right methods with the wrong arguments (another resource, a right checked on another subject) passes it. The nominal case catches a wrong input, not a wrong subject of a right.
- A right that a tool checks under a condition (`stop-prospecting` needs the personal data only for an organization with contacts) passes the wiring test whatever the condition. Such a tool keeps a test of its condition.
- The wiring test follows the calls on `$this`, on the injected services, on the helpers of an `Mcp` namespace and on the inputs. A tool that reaches its use case another way needs the test extended.

## Alternatives considered

None: the ticket of 24/11 asked for this rule.

## Revisit when

A tool lets a member do what its endpoint refuses them, while the wiring test is green.
