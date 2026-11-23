# 0197. Each context has its own identities; only the account and the member are shared

- Date: 2026-11-23
- Type: architecture
- Status: Accepted
- Supersedes: for the shared kernel's list, [0030](0030-bounded-contexts-accounts-prospecting.md); for "the other contexts keep a `PersonId`", [0031](0031-personal-data-context.md); for "`OrganizationId` becomes a published identity of the shared kernel", [0042](0042-contacts-never-alone-domain-events.md); for "`CampaignId`, now in the shared kernel", [0145](0145-the-exchange-of-a-recipient-is-noted-by-prospecting-which-asks-campaigns-and-campaigns-processes-the-recipient-from-its-facts.md); for "`MessageId` of the shared kernel", [0166](0166-an-exchange-is-fingerprinted-by-the-core-noted-once-in-its-account-by-an-ai-or-an-import-even-erased.md)
- Implemented by: `packages/core/src/<Context>/Domain/Model/{OrganizationId,PersonId,CampaignId,ContactId,ProfileId,BookerId,MessageId}`, `packages/core/tests/Architecture/SharedIdentitiesTest`, `packages/core/tests/Domain/MessageIdTest`, `docs/architecture/core.md`

## Context

The shared kernel held `PersonId`, `OrganizationId`, `CampaignId` and `MessageId`, used by up to seven contexts. A shared identity says that a concept is the same everywhere, which is false. For Campaigns, an organization holds tags. For PersonalData, it is a contact's organization. For Reporting, it is what facts are about. Campaigns already turned its holder's identity into an `OrganizationId`. The project had already given three contexts their own `ExchangeId`. The Contracts import none of these identities: the language the contexts publish is made of strings, so only the domains were coupled. The identities are empty classes, so copying them costs almost nothing. `AccountId` and `MemberId` mean the same everywhere (the tenant, and who acted), and `DomainEvent` carries them.

## Decision

- A context has its own identity, in its `Domain/Model`, for each thing it refers to in another context. It names that identity in its language.
- The context that keeps the aggregate keeps its identity: `OrganizationId` in Prospecting, `PersonId` in PersonalData, `CampaignId` in Campaigns.
- Another context keeps the same name when its word is the same, as `ExchangeId` does: `OrganizationId` in Campaigns, Connectors, PersonalData and Reporting, `CampaignId` in Prospecting. It takes its own word otherwise: `ContactId` in Prospecting, Campaigns, Connectors and Reporting, `ProfileId` in Accounts (a member's profile), `BookerId` in Booking.
- A context translates another one's identity where it receives it: in the anticorruption adapter of its port, or in the double of that port, from the other context's string.
- Connectors and Prospecting each have a `MessageId`. It is a value object with a rule (an e-mail's `Message-ID` header, trimmed, in lower case, hashed by SHA-256), and both contexts must apply it alike. Otherwise an imported e-mail and the same e-mail an AI logs get different fingerprints. One unit test runs on both copies and pins the hash.
- Only `Uuid`, `AccountId` and `MemberId` stay in the shared kernel. `tests/Architecture/SharedIdentitiesTest` fails and names any other identity added there.
- Nothing stored changes: an event carries an identity as its string, and is read back by its parameter's type.
- The rule also covers what backlog #268 moved: a context keeps the parents of its events.

## Consequences

- An identity no longer couples two contexts' domains. A context can change how it refers to another context's things without touching that context.
- Passing one context's identity to another is a type error, caught by PHPStan.
- A file that speaks to two contexts (a scenario's context, a fixture, the demonstration data) imports both identities, one of them aliased.
- The two `MessageId` copies must change together. Their shared test is the only guard.

## Alternatives considered

- Keeping `MessageId` shared, since it is a value object with one rule, like `Email`: the ticket lists the message among the identities to copy. The shared test keeps the two copies in line.

## Revisit when

- A context needs a behaviour on another context's identity: that behaviour belongs to the context that keeps the aggregate, through its Contract.
