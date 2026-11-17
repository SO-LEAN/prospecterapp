# 0145. The exchange of a recipient is noted by Prospecting, which asks Campaigns, and Campaigns processes the recipient from its facts

- Date: 2026-11-18
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/src/Prospecting/Application/NoteCampaignExchange`, `packages/core/src/Prospecting/Domain/Model/RecipientInCampaign.php`, `packages/core/src/Prospecting/Domain/Port/{CampaignRecipients,CampaignNames}.php`, `packages/core/src/Prospecting/Application/CampaignsOfTimeline.php`, `packages/core/src/Campaigns/Contract/Service/{WhichRecipientsArePending,NameCampaigns}`, `packages/core/src/Campaigns/Application/{DismissRecipient,ProcessRecipientOfCampaignExchange}`, `packages/core/src/SharedKernel/Domain/CampaignId.php`, `packages/core/features/mener-une-campagne.feature`, `apps/api/src/Prospecting/AntiCorruption/{CampaignsCampaignRecipients,CampaignsCampaignNames}.php`, `apps/api/src/Prospecting/Controller/NoteCampaignExchangeController.php`, `apps/api/src/Campaigning/Controller/DismissRecipientController.php`, `apps/api/config/subscriptions.json` (`campaigns-process-recipient`), `apps/front/src/features/campaigns/components/campaign-run.tsx`

## Context

A started campaign is led contact by contact: the member contacts a recipient themselves, then notes the exchange, which goes in the timeline of the contact like any other, and the recipient is processed; or the recipient is dismissed without an exchange; the campaign ends once nobody is left to handle (ADR 0144). One gesture touches two aggregates of two contexts: the exchange, of Prospecting, and the campaign, of Campaigns. A use case changes one aggregate, the others following from its events (ADR 0042). Campaigns is downstream of Prospecting (ADR 0138) and published nothing until now.

## Decision

- Prospecting notes the exchange first (`NoteCampaignExchange`). Before, it asks Campaigns, by its published service `WhichRecipientsArePendingService`, whether the contact is a recipient of a campaign of the account, still to handle, whether the campaign is started, and its channel. It refuses an unknown campaign (`UnknownCampaign`, a campaign of another account included), a campaign not started (`CampaignNotRunning`), a contact who is no recipient (`UnknownRecipient`), a recipient handled already (`RecipientAlreadyProcessed`). The exchange takes the channel of the campaign, outbound, done, or missed for a call that did not go through, and keeps the identity of its campaign, `CampaignId`, now in the shared kernel.
- `ExchangeFactsChanged.v3` names the contact of the exchange and its campaign; `ExchangeFactsChanged.v2` is still read, without them. The reaction `ProcessRecipientOfCampaignExchange` of Campaigns (the subscription `campaigns-process-recipient`, the facts present, done or missed) processes the recipient, done or missed, and ends the campaign once nobody is pending. It is idempotent, without inbox: delivered again, for an exchange of no campaign, a campaign erased or no longer started, or a recipient handled already, it changes nothing. A reaction of the system without a member, `Campaign::process()` records no event, like `forget()`.
- Campaigns dismisses a recipient by its own command (`DismissRecipient`, the event `RecipientDismissed`), ending the campaign the same way; so does the last recipient to handle erased by PersonalData (`ForgetRecipientOfForgottenContact`). Ended, a campaign offers nothing.
- The timeline names the campaign of an exchange by a second published service of Campaigns, `NameCampaignsService`, asked once per slice; nothing of the campaign is copied onto the exchange but its identity.
- Two exchanges noted for one recipient before the reaction ran are both kept: Prospecting refuses the second only once the first processed the recipient, and the reaction keeps the recipient processed by the first.
- The screen does not wait for the reaction: once the exchange is noted, it holds the recipient handled and opens the next one to handle, the campaign and its recipients read again at once and once the reactions ran (`readAgainAfterTheReactions`, ADR 0101).

## Consequences

- An upstream context asks a downstream one at the noting of a campaign exchange: Prospecting depends on the Contract of Campaigns, through its ports `CampaignRecipients` and `CampaignNames` and their anticorruption layers.
- A recipient is processed a moment after its exchange is noted, by the worker; the progress of the campaign follows. The race above may keep two exchanges of one recipient in a campaign.
- Removing an exchange of a campaign does not bring its recipient back to handle (a later ticket).

## Alternatives considered

The ticket left open the order of the two contexts: Campaigns first, a command of Campaigns processing the recipient and Prospecting noting the exchange in a reaction, or Prospecting first. The maintainer chose Prospecting first.

## Revisit when

Several members lead the same campaign at the same time and two exchanges of one recipient become frequent.
