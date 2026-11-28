# 0226. What an AI created reads « IA » on every screen

- Date: 2026-11-28
- Type: domain
- Status: Accepted
- Supersedes: for the label the screens show, [0185](0185-what-an-ai-creates-through-the-mcp-server-keeps-its-origin-set-by-its-tools.md)
- Implemented by: `CreatedByAnAIBadge` and `AIMarker` of the front (`shared/ui/created-by-an-ai-badge.tsx`, `shared/ui/event/markers.tsx`)

## Context

ADR 0185 says that the screens show « MCP » on what an AI created, and « Créé par une IA » on hover. The calendar's events, and then the exchange's line that every timeline shares, show an exchange's markers. Their AI marker reads « IA ». So one exchange read « IA » on its line and « MCP » in its detail, and an organization or a contact read « MCP » on its card.

## Decision

The maintainer chose one label for every screen: « IA ». An organization, a contact and an exchange that an AI created read « IA », on their card, in their detail, on their line and in the calendar. Hovering and screen readers still give « Créé par une IA ». The AI is still never named. The rest of ADR 0185 stays in force.

## Consequences

- « MCP » no longer appears on any screen.
- A new screen that shows the origin uses `CreatedByAnAIBadge`, or `AIMarker` among an event's markers.

## Alternatives considered

- Keep « MCP » everywhere, the line's and the calendar's markers included: the maintainer chose « IA ».

## Revisit when

Prospecter learns which AI acts (ADR 0185).
