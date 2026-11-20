# 0166. An exchange is fingerprinted by the core, noted once in its account by an AI or an import, even erased

- Date: 2026-11-20
- Type: architecture
- Status: Accepted
- Supersedes: for the marks `IMPORTED_EMAIL` of the e-mails imported, read by `ExchangeRepository::wasImported()`, [0154](0154-gmail-is-pushed-through-a-topic-of-the-google-cloud-of-the-account.md); for a refusal of the core always answered as the error of the tool, and the result of a tool always the resource of its endpoint, [0163](0163-every-endpoint-of-a-member-has-its-mcp-tool-but-those-kept-on-the-screen.md)
- Implemented by: `packages/core/src/SharedKernel/Domain/MessageId.php`, `packages/core/src/Prospecting/Domain/Model/{ExchangeFingerprint,MessageReference,Fingerprinted,Erasure}.php`, `packages/core/src/Prospecting/Domain/Service/NotedOnce.php`, `packages/core/src/Prospecting/Domain/Exception/{ExchangeAlreadyNoted,ExchangeErasedBefore,ExchangeNotedMeanwhile,ReferenceNotOfTheChannel}.php`, `packages/core/src/Prospecting/Application/{NoteExchange,NoteOrganizationExchange,NoteImportedEmail,ForgetFingerprintsOfForgottenContact}`, `packages/core/features/l-empreinte-d-un-echange.feature`, `apps/api/src/Prospecting/Persistence/{DynamoExchangeRepository,ExchangeDocument,ExchangeKeys}.php`, `apps/api/src/Prospecting/Mcp/NoteExchangeTool.php`, `apps/api/src/Contacts/Mcp/NoteOrganizationExchangeTool.php`, `apps/api/src/Exchanges/NotedOnceAnswers.php`

## Context

An AI synchronizing a mailbox or a LinkedIn messaging through the MCP server reads again messages it already noted; the import of Gmail (0154) notes the same e-mails. Decided on 2026-11-18 (the epic of the MCP server): Prospecter computes the fingerprint of an exchange itself, never the AI, so that it is the same whatever the AI; an exchange noted is refused without error, the AI answered « already noted »; an exchange erased does not come back; one registry of the fingerprints per account, shared with the import of Gmail, so that an e-mail imported is not noted again by an AI, and conversely; the forgetting of a contact erases the fingerprints of their exchanges. The import kept a mark per exchange imported, `IMPORTED_EMAIL#<exchange>`, its identity derived from the account, the e-mail and its correspondent (0154). The commands of the core return nothing (CQS), and a refusal of the core was the error of the tool (0163).

## Decision

- **The fingerprint** (`ExchangeFingerprint`, a SHA-256, never given by a client) is computed by the core when the exchange is noted, from what the AI saw (`MessageReference`, not kept): an e-mail by the identity of its `Message-ID` (without chevrons, spaces nor case, hashed: `MessageId` of the shared kernel, the identity Connectors gives an e-mail of Gmail) and its correspondent, the contact or the organization, as the identity 0154 derives (the maintainer, 2026-11-20: an e-mail sent to two contacts is an exchange with each); a LinkedIn message by its conversation (its identifier, read from the address `linkedin.com/messaging/thread/<id>/` too), its direction, its minute (UTC) and its text without case, punctuation nor extra spaces; any other exchange, and an e-mail or a LinkedIn message given without reference, by its channel, its correspondent, its direction, its minute and its text alike. A reference of another channel is refused (`ReferenceNotOfTheChannel`, 422). The exchanges noted with a contact or an organization, and those imported, are fingerprinted; the planned, booked and campaign ones are not.
- **One registry per account** (`ExchangeRepository::fingerprinted()`): `FINGERPRINT#<account>#<fingerprint>` / `FINGERPRINT`, the exchange noted under it, written with the new exchange under `attribute_not_exists(PK)` (lost, `ExchangeNotedMeanwhile`, 409), and, once the exchange is erased, by a member or with its organization, who erased it and when; listed among the fingerprints of its contact in `GSI1` (`FINGERPRINTS#<account>#<contact>`). It replaces the marks `IMPORTED_EMAIL`: the import refuses itself an e-mail whose fingerprint is registered, by an AI, the import or by hand. An exchange imported before reads with the fingerprint of its e-mail, registered by `app:keys:rebuild`; the marks written on dev stay, unread.
- **Noted once** (`NotedOnce`, a domain service): noted by an AI (the command `noteOnce`, set by the tools), an exchange whose fingerprint is registered is refused, `ExchangeAlreadyNoted` with the exchange noted first, or `ExchangeErasedBefore` with who erased it and when (409); noted by hand, never refused (the maintainer, 2026-11-20): its fingerprint is registered when none is yet, otherwise it is left without one, so that an AI or the import does not note it again.
- **Forgotten**: the forgetting of a contact forgets the fingerprints of their exchanges, erased or not (`ForgetFingerprintsOfForgottenContact`, a reaction to `ContactForgotten`), and an exchange kept to make conform (0079) drops its own.
- **The tools** `noter-un-echange` and `noter-un-echange-organisation` adapt the endpoints noting an exchange, with `messageId` and `linkedInConversation`, and answer, instead of the timeline of the endpoint, `{status, exchange, erasedBy, erasedAt}` (`NotingResource`): `noted` with the exchange as `lire-echange` reads it; `alreadyNoted` with the exchange noted first; `erasedBefore` with the member who erased it, named as the authors of an exchange, and when; both refusals of the core so answered as a result, never as the error of the tool, so that the AI goes on. Any other refusal stays the error of the tool, `ExchangeNotedMeanwhile` among them.

## Consequences

- An AI synchronizes as often as it likes, nothing doubled: the core decides alone what is the same message, the same for every AI and for the import.
- A member noting by hand the same call twice notes it twice; an AI noting it afterwards is answered the first.
- An exchange erased by mistake by a member is not noted again by an AI; the member notes it by hand.
- Two notings of the same exchange at once: the second one fails as the error of the tool (409 `exchangeNotedMeanwhile`), the AI noting it again answered « already noted ».
- A message corrected after it was noted keeps the fingerprint of what was read first.
- The registry holds a hash per exchange, its erasure and its contact, kept as long as the account, but for a contact forgotten; its forgetting writes, in the unit of its reaction, a delete per fingerprint of the contact, as many as their exchanges (100 writes at most per unit).

## Alternatives considered

- The fingerprint of an e-mail by its `Message-ID` alone, as the ticket first wrote: an e-mail sent to two contacts would be one exchange; the maintainer chose the pair of 0154 (2026-11-20).
- Noting by hand refused as a duplicate too, one rule for the screen and the AI: a member erasing a call noted by mistake could never note it again; the maintainer chose the screen never refused (2026-11-20).

## Revisit when

An AI or a member needs a message erased noted again, or the sent e-mails are imported with their recipients in « À ».
