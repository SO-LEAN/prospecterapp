# 0152. The erasure of the exchange of a recipient brings them back to handle, from the last facts of the exchange

- Date: 2026-11-18
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/src/Campaigns/Domain/Model/{Campaign,Recipients,Recipient}.php`, `packages/core/src/Campaigns/Application/ReopenRecipientOfErasedExchange`, `packages/core/src/Prospecting/Domain/Event/ExchangeErased.php`, `packages/core/src/Prospecting/Application/PublishExchangeFacts/PublishExchangeFactsHandler.php`, `packages/core/features/aller-d-une-campagne-a-ses-echanges.feature`, `apps/api/config/subscriptions.json` (`campaigns-reopen-recipient`), `apps/api/src/Shared/Messaging/DomainEventTypes.php` (`ExchangeErased.v2`), `apps/api/src/Campaigns/Persistence/CampaignDocument.php`, `apps/api/src/Campaigning/RecipientsReader.php`, `apps/api/src/Exchanges/ExchangeDetailPresenter.php`

## Context

A recipient of a campaign is processed by the exchange noted for them, from its facts (ADR 0145), which left for later the erasure of that exchange. The member now goes from a recipient to their exchange and back, and erasing the exchange must bring the recipient back to handle. Once erased, the exchange can no longer be read: its terminal fact was absent, naming neither its contact nor its campaign.

## Decision

- A recipient processed keeps the identity of the exchange that processed them (`Recipient::$exchangeId`, kept in the item of the campaign); the sheet of the campaign gives it, and the API links it (`exchange`). The detail of an exchange names its campaign as Campaigns names it now, through the port `CampaignNames` of ADR 0145, and links it (`campaign`) to those who read the campaigns, while the campaign is kept.
- `ExchangeErased` names the contact and the campaign of the exchange (`ExchangeErased.v2`, `.v1` still read without them), and its terminal fact, `ExchangeFactsChanged.v3` absent, names them too: Prospecting publishes them from the event, the exchange no longer being read.
- The reaction `ReopenRecipientOfErasedExchange` of Campaigns (the subscription `campaigns-reopen-recipient`, the facts absent) brings back to handle the recipient this very exchange processed, an ended campaign started again (its end forgotten), a stopped one staying stopped. Idempotent, without inbox: delivered again, for an exchange of no campaign, a campaign erased, a recipient erased since or processed by another exchange, it changes nothing. A reaction of the system, `Campaign::reopenAfterErasureOf()` records no event, like `process()`.

## Consequences

- A recipient processed before this record has no exchange kept: neither linked nor brought back to handle when that exchange is erased.
- The terminal fact of an exchange is no longer bare: Reporting and Connectors ignore its contact and its campaign.

## Alternatives considered

None were weighed: the ticket asked for the behaviour, the reaction to the facts follows ADR 0145.

## Revisit when

A campaign must keep the trace of an exchange erased, or of several exchanges of one recipient.
