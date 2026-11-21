# 0185. What an AI creates through the MCP server keeps its origin, set by its tools, the AI never named

- Date: 2026-11-21
- Type: domain
- Status: Accepted
- Implemented by: `Origin` of the shared kernel, the commands `NoteExchange`, `NoteOrganizationExchange`, `PlanExchange`, `PlanOrganizationExchange`, `StartProspecting`, `CollectContact` (`byAnAI`), the tools of `apps/api/src/*/Mcp` and `Identification\ContactRecorder`, `Documents::originOf()`, `CreatedByAnAIBadge` of the front

## Context

A member wants to see at a glance what an AI created for them through the MCP server (ADR 0156): an organization, a contact, an exchange. Every AI signs in through the same facade on Cognito, which keeps nothing (ADR 0157): Prospecter knows an AI acted, not which one, and the maintainer decided the badge names none. The firewall `mcp` cannot tell an AI either: an access token of the SPA given by hand opens `/mcp` too.

## Decision

- The organizations, the contacts and the exchanges keep their origin, `Origin::Hand` or `Origin::Ai` (shared kernel), given once by the intention creating them; nothing changes it after: a correction by hand, or an AI completing what was created by hand, keeps it.
- The command creating one carries `byAnAI` (an exchange noted or planned, with a contact or an organization, an organization started, a contact collected); the tools of the MCP server creating something set it, the controllers never: the tool is what tells that an AI acts, whatever the token.
- Its item keeps it (`origin`), one written before reads as created by hand; the resources say `createdByAnAI`, the screens show « MCP », « Créé par une IA » on hover.
- An exchange imported from Gmail keeps its own mark (`importedFrom`, ADR 0174), its origin by hand.

## Consequences

- A tool creating something added later sets `byAnAI` on its command, or what it creates reads as by hand.
- A member giving the access token of the SPA to a client of the protocol by hand creates through it as an AI: the badge says the MCP server, not a model.
- The origin of what existed before stays by hand, nothing is rebuilt.

## Alternatives considered

- The origin set by the firewall `mcp` from the member signed in, as the ticket first suggested: it would name a token rather than a gesture, and the commands would read the security of the API; the tools already are the boundary of the MCP server.
- The name of the AI (its client): refused by the maintainer, the facade keeps nothing that would tell it (ADR 0157).

## Revisit when

Prospecter learns which AI acts (a client registered per AI), or another channel than the MCP server creates for the member (an import of LinkedIn).
