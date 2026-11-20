# 0163. Every endpoint of a member has its MCP tool, but those kept on the screen

- Date: 2026-11-20
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/src/*/Mcp/*Tool.php`, `apps/api/src/Shared/Mcp` (`Adapts`, `ToolResults`, `ToolInputs`, `RefusingTools`), `apps/api/src/Shared/DependencyInjection/RefusingToolsPass.php`, `apps/api/src/Security/ToolAccess.php`, `apps/api/src/Security/AccessProblems.php`, `apps/api/src/Shared/RefusalProblems.php`, `apps/api/tests/Api/ReadingToolsApiTest.php`, `apps/api/tests/Architecture/EveryEndpointHasItsToolTest.php`

## Context
The MCP server (ADR 0156) had one tool, `qui-suis-je`. The maintainer wants an AI signed in as a member (ADR 0157) to read everything the member reads on the screens, and the server never to lag behind the application: an endpoint added without its tool would leave the AI blind to it, and nobody would notice.

## Decision
- Every endpoint a member calls, a route whose controller declares `#[IsGranted]`, has its MCP tool: a thin adapter beside its controller, `src/<Context>/Mcp/<Intent>Tool.php`, naming the endpoint by `#[Adapts('<route>')]`, calling the same reader, query or command of the core. No business rule in the MCP server. The public routes (the facade of ADR 0157, the public booking page, the push of Gmail, the user pool, the documentation) are no endpoints of a member.
- A tool checks what the attributes of its controller check, in their order: the resource read first (an unknown one refused by the core), then the role, the voter of the resource (another account: a 404 named as the refusal of the core, ADR 0081), the right on the personal data (`Security\ToolAccess`).
- Its result is the resource of the endpoint, serialized as the endpoint serializes it, its hypermedia links included, for the AI to go from one resource to another; its arguments, those of the route and of the query of the endpoint, checked by the same input. A refusal is the error of the tool, its text the problem the endpoint answers (`Shared\Mcp\RefusingTools`, decorating the runner of the tools of the SDK).
- The tools of reading carry the annotation `readOnlyHint`.
- `tests/Architecture/EveryEndpointHasItsToolTest` fails naming each endpoint of a member without a tool, but those of two lists: « jamais », kept on the screen (the forgetting of a contact, GDPR; the credentials of the sign-in, login email and password; the connection of a connector, its consent given to Google in a browser), and « à couvrir », the writes, which the next tickets cover, the last of them removing the list. No endpoint of the members and their roles exists yet; it will be on the list « jamais ».
- `tests/Api/ReadingToolsApiTest` proves each tool answers what its endpoint answers, the resource or its refusal, to a prospector, an assistant without the right on the personal data, an administrator, a member who is no prospector and a member of another account.

## Consequences
- An endpoint added comes with its tool and its line in the equivalence test, or names itself on a list: the CI says so.
- Some forty tools, each a few lines; the compositions a controller did inline (the search of the contacts, by text and by tags, the contacts of an organization, who calls, the dashboard, the download of an attachment, the message of a campaign) moved to readers both share.
- The resolvers of the read models (organization, exchange, campaign, to-do) read one by its id for the tools too.
- The tools are listed by namespace in the `registry` of the server: a context getting its first tool adds its namespace.

## Alternatives considered
None: the maintainer settled the rule, the two lists and the results with their links in the framing of the ticket.

## Revisit when
The writes are covered (the list « à couvrir » removed), or an endpoint is meant for the screen only without being on the list « jamais ».
