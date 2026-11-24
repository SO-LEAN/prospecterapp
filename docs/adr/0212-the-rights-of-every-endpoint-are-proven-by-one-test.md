# 0212. The rights of every endpoint are proven by one test

- Date: 2026-11-24
- Type: architecture
- Status: Accepted
- Supersedes: for the rights per role of an endpoint, proven by its own API tests, [0208](0208-a-new-api-test-proves-only-the-adaptation.md)
- Implemented by: `apps/api/tests/Api/EndpointRightsApiTest.php`, the rights tests removed from `apps/api/tests/Api`, `docs/architecture/tests.md`, `api.md`

## Context

The rights are no core rule: the API's voters and the roles of the controllers check them (ADR 0081). Each file of `tests/Api` wrote its own tests of rights: the assistant refused, the prospector without the personal data refused, another account's resource not found, the caller without a token refused. A new endpoint added a series of such tests. Each controller already declares its rights by `#[IsGranted]`: its roles, the voter of its resource, `User::PERSONAL_DATA`.

## Decision

- The `#[IsGranted]` attributes of the controllers are the table of the rights. The access check is part of the primary adapter, and the voters exist already.
- `tests/Api/EndpointRightsApiTest` reads them for every route of the members' firewall. It calls each endpoint over HTTP with each profile: the assistant, the prospector, the prospector with the personal data and the administrator. A profile holding the roles passes, any other gets a 403 `roleNotAllowed`. The request crosses the firewall, the role hierarchy and the real voter, so the test proves their verdict and not only the wiring. A granted request stops before its controller, so the test changes nothing.
- The same test calls each endpoint of a resource as a member of another account (404 `unknown<Resource>`), and each endpoint without an access token (401).
- A controller of a member without `#[IsGranted]` fails the test, which names it.
- A rights test that the table covers is removed. A right that depends on the resource's state is checked in the controller or the core, and keeps a test of its own. So do the links a presenter shows per role.

## Consequences

- A new endpoint declares its rights by its attributes and adds no test for them.
- The test proves that the attributes are enforced, not that they are the right ones: a wrong attribute is read in the review of the controller.
- A right checked in a controller's code (`StopProspectingController`) is not in the table. Its condition keeps its own test.
- A new path variable without a value in the test, or a new voter, needs the test extended: it fails and names the path.
- The MCP tools check the same attributes (ADR 0211), so their rights follow those this test proves.

## Alternatives considered

None: the maintainer decided on 24/11 that the table is read in the `#[IsGranted]` attributes and that the test calls each endpoint over HTTP.

## Revisit when

A right comes to depend on something the attributes cannot say, for most endpoints.
