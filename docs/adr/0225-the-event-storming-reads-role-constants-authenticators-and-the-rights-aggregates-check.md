# 0225. The event storming reads role constants, authenticators and the rights aggregates check

- Date: 2026-11-28
- Type: architecture
- Status: Accepted
- Implemented by: `packages/phpstan-rules/EventStorming` (`ClassFactsCollector`, `ClassFacts`, `Facts`, `ContextStorming`, `Sticky`, `Wording`), `apps/front/event-storming/wall.ts`, `apps/front/event-storming/story.ts`, `apps/api/src/Connectors/Controller/NoteGmailNotificationController.php`, `apps/api/tests/Architecture/EveryEndpointHasItsToolTest.php`, `docs/architecture/event-storming.md`

## Context

ADR 0215 reads an actor from the role that a controller's `#[IsGranted]` requires. The walls still lost or misnamed some actors:

- The analysis read only a string literal. `#[IsGranted(User::PERSONAL_DATA)]` names a class constant, so its 39 controllers required no role. A controller with no other attribute read as "Anyone with the link".
- `GmailPushAuthenticator` reads the mailbox pusher before any controller runs. The analysis read no authenticator, so that view carried "Nobody calls this".
- The Gmail webhook had no `#[IsGranted]`: only the firewall's `access_control` required `ROLE_GMAIL_PUSHER`. Its command read as sent by anyone with the link.
- A right that the aggregate checks itself (ADR 0193), such as `Person::collectAsContact()` and `Member::registerBy()`, stood on no wall.

## Decision

- The analysis resolves the class constants that an `#[IsGranted]` attribute names. It keeps only the values that start with `ROLE_`, the only ones Symfony's role voter grants. A voter's attribute (`OrganizationVoter::ACCESS`) is no role.
- An authenticator of the security (`AuthenticatorInterface`) is an entrance. Its actor is each role of the user it creates (`UserInterface`), read from that user's constants. `GmailPushAuthenticator` creates a `GmailPusher`, whose role reads "Google".
- The Gmail webhook's controller requires `GmailPusher::ROLE` by its attribute, as the firewall already did. It is no member's endpoint, so it needs no tool of the MCP server (ADR 0163).
- Google looks at no screen, like the platform operator and the daily schedule. What it sends never asks "Offered where?".
- A command names the rights that its aggregates check: each refusal of access (a class that extends the shared kernel's `NotAllowed`) that the intentions it reaches may raise, through the domain's classes. Each right is named after its refusal ("Administrators only"), in the card of the command.
- `ROLE_PERSONAL_DATA` reads "Personal data manager".

## Consequences

- No "Anyone with the link" is left on the Connectors wall.
- A guard that picks its refusal by the intention, like `Person`'s check of the reader, gives every intention calling it each refusal it may raise. The card then names a right that the intention may not check. The card says "refusals of access", not a certain rule.
- The refusals of access that are no role (`NotTheHolder`, `AccessRevoked`) are named too.
- The generator recognizes Symfony's interfaces by their short names, as it does `ScheduledTask` (ADR 0215).

## Alternatives considered

The ticket left the way the aggregate's check reaches the wall to the developer: a parameter typed `Reader`, a guard method, or an attribute. A parameter typed `Reader` would miss `Member::registerBy()`, whose administrator is a `Member`. An attribute in the core would be metadata written by hand for a tool, which ADR 0215 refused. The refusal of access that the code raises was kept.

## Revisit when

A card names a right that its use case never checks and misleads, or a new kind of refusal of access does not extend `NotAllowed`.
