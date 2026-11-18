# 0156. An MCP server without session in the Lambda of the API, on /mcp, its callers the members signed in on Cognito

- Date: 2026-11-18
- Type: architecture
- Status: Accepted
- Implemented by: `docs/architecture/mcp.md` (the tickets after the spike #202 build it; its proof, the branch `spike/202-mcp-proof`, never merged)

## Context
Prospecter wants to be reached by AIs (Claude web first, Claude Code, any client of the Model Context Protocol). The API runs in a Lambda (ADR 0053, 0063): no process lives between two requests, two requests may reach two instances, and nothing in the stack keeps a session. The protocol had a handshake (`initialize`) and a session (`Mcp-Session-Id`) until its revision `2026-07-28`, which removed both; the clients still speak either. The maintainer decided that an AI acts as the member it signed in, with exactly their rights: no access of an AI in the domain, no scope of its own.

## Decision
- The API serves the MCP server, `symfony/mcp-bundle` (Symfony AI) on `mcp/sdk`, HTTP « streamable », on `/mcp` of its host, in its Lambda, behind its HTTP API: no infrastructure of its own.
- Without session (`session.store: none`): both eras on the same endpoint, the SDK routing each request by its `params._meta`; the clients of the handshake answered without `Mcp-Session-Id`, without the requests of the server, the subscriptions and the logging, which no tool needs.
- Both libraries are experimental: pinned at an exact version, moved by hand, their changelogs read. `none` came after the last tag of the bundle (`v1.0.0-BETA1`, `mcp/sdk ^0.9`), on its `main` with `mcp/sdk` 0.10.0: the proof pinned that commit (`790442a8`); the first ticket shipping the server takes the first tag carrying it, or pins that commit.
- A firewall `mcp` checks the access tokens of Cognito as the firewall `api` does, those of the client of the AIs beside those of the SPA, and loads the same member, with their roles: an AI reads the personal data in clear or masked as its member does. Without a valid token, 401 naming the metadata of the protected resource (RFC 9728), the only answer on which Claude starts its sign-in.
- A tool is an adapter of one intent, `src/<Context>/Mcp/<Intent>Tool.php`, reading the member through `Security` and calling the core as a controller does.
- The rate of `/mcp` is limited as that of the other public routes of the API: by address by the edge of the installation, and by the throttling of the routes of the HTTP API (`apps/api/infra`), no provider named here.

## Consequences
- Proven in the Lambda on MiniStack: a client of each era called a tool and read the member and their account; the MCP Inspector too.
- One Lambda, one deployment, one way of checking who calls; nothing to keep between requests, nothing to share between instances.
- A client of the handshake era gets no request of the server (elicitation, sampling): a tool needing one would need a session store shared by the instances, or the revision `2026-07-28` and its multi-round-trip answers.
- An experimental bundle, its configuration may break at a bump: the pin and the changelog read are its price; until a tag carries `none`, a commit of `main`.
- The tools follow the access of the controllers: a role missing refuses the tool (an error of the tool, the HTTP answer staying 200), no new rule of access.

## Alternatives considered
- `session.store: file` of the last tag (`v1.0.0-BETA1`), weighed by the spike: the sessions of a handshake client in the `/tmp` of one instance of the Lambda, lost on the next instance; a shared store (cache, framework session) would bring a store the stack does not have. Refused.
- A scope of its own for the AIs, or an access of an AI kept in the domain (Accounts), proposed by the first plan of the spike: refused by the maintainer, an AI being its member signed in.

## Revisit when
A tool needs to ask the member something during a call (elicitation), or the bundle reaches a stable release.
