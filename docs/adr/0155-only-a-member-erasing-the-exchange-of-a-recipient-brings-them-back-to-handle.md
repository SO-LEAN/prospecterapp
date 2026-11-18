# 0155. Only a member erasing the exchange of a recipient brings them back to handle, the facts of an exchange gone with its organization saying so

- Date: 2026-11-18
- Type: architecture
- Status: Accepted
- Supersedes: for the exchange erased with its organization, the reopening of [0152](0152-the-erasure-of-the-exchange-of-a-recipient-brings-them-back-to-handle-from-the-last-facts-of-the-exchange.md)
- Implemented by: `packages/core/src/Prospecting/Domain/Model/Exchange.php` (`eraseWithItsOrganization()`), `packages/core/src/Prospecting/Domain/Event/ExchangeErased.php`, `packages/core/src/Prospecting/Contract/Event/ExchangeFactsChanged.php`, `packages/core/src/Prospecting/Application/EraseExchangeLeftWithoutOrganization`, `packages/core/src/Campaigns/Application/ReopenRecipientOfErasedExchange`, `packages/core/features/aller-d-une-campagne-a-ses-echanges.feature`, `apps/api/src/Shared/Messaging/{IntegrationEventTypes,DomainEventTypes,Payloads}.php`, `apps/api/config/subscriptions.json`

## Context

A started campaign keeps an erased contact with what the team did with them. Stopping to prospect the last organization of a contact erases them, and erases too the exchanges no organization keeps any more, through two chains of reactions the worker runs in no order (`messaging.md`). When the erasure of the exchange came first, the reopening of ADR 0152 brought the recipient back to handle, then the erasure of the contact made them no longer to handle: the call done or missed was lost.

## Decision

- Only a member erasing the exchange of a recipient brings them back to handle. An exchange erased because no organization keeps it any more (`Exchange::eraseWithItsOrganization()`, by `EraseExchangeLeftWithoutOrganization`) says so: `ExchangeErased.v3` and its terminal fact `ExchangeFactsChanged.v4` carry `withItsOrganization` / `erasedWithItsOrganization`, and `ReopenRecipientOfErasedExchange` ignores such a fact.
- The former versions (`ExchangeErased.v1`, `.v2`, `ExchangeFactsChanged.v1` to `.v3`) are still read, as an erasure by a member: a parameter a former version did not carry takes the default of its constructor (`Payloads`), null when it has none.

## Consequences

- Stopping to prospect an organization never brings a recipient back to handle, even when the contact stays attached to another organization: the recipient stays processed, without the exchange to lead to.
- An exchange erased with its organization from a version published before this record, still in an outbox or a queue of failures, may reopen its recipient once.

## Alternatives considered

Asked to the maintainer for ticket #194, three options: this one; Campaigns refusing to reopen a recipient whose contact PersonalData no longer knows, which lowers the race without ending it, the contact being possibly not erased yet; leaving the race out of the ticket. The maintainer chose this one.

## Revisit when

A member must see the recipient back to handle when the prospecting of one of the organizations of a contact still prospected stops.
