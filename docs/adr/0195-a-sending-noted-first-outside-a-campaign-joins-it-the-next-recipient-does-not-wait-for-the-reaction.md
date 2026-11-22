# 0195. A sending noted first outside a campaign joins it, and the next recipient does not wait for the reaction

- Date: 2026-11-22
- Type: architecture
- Status: Accepted
- Supersedes: for the gap left open (the import of Gmail noting the e-mail of a campaign first), [0186](0186-an-ai-leads-a-campaign-sending-each-message-itself-prospecter-notes-it-once.md); for the next recipient read once the reaction processed the recipient, [0145](0145-the-exchange-of-a-recipient-is-noted-by-prospecting-which-asks-campaigns-and-campaigns-processes-the-recipient-from-its-facts.md)
- Implemented by: `packages/core/src/Prospecting/Domain/Model/{Exchange,RecipientInCampaign}.php`, `packages/core/src/Prospecting/Domain/Event/ExchangeJoinedCampaign.php`, `packages/core/src/Prospecting/Domain/Service/NotedOnce.php`, `packages/core/src/Prospecting/Application/{NoteCampaignExchange,SendingNoted,WhichContactsHaveExchangesOfCampaign}`, `packages/core/src/Prospecting/Contract/Service/WhichContactsHaveExchangesOfCampaign`, `packages/core/src/Campaigns/Domain/Port/CampaignExchanges.php`, `packages/core/src/Campaigns/Domain/Model/{Campaign,Recipients}.php`, `packages/core/src/Campaigns/Application/NextRecipient`, `packages/core/features/mener-une-campagne-avec-une-ia.feature`, `apps/api/src/Campaigns/AntiCorruption/ProspectingCampaignExchanges.php`, `apps/api/src/Prospecting/Persistence/DynamoExchangeRepository.php`, `apps/api/src/Prospecting/Mcp/NoteCampaignExchangeTool.php`, `apps/api/src/Campaigning/Mcp/LeadCampaignPrompt.php`

## Context

An AI leading a campaign could not move on in two cases, both met in QA. The import of Gmail notes the e-mail the AI sent about two seconds after it is sent, with no campaign. When the AI then notes it, `log-sent-message` answers « already noted »: the recipient stays to handle, and `get-next-recipient` gives them again (the gap ADR 0186 left open). And the recipient is processed by a reaction of Campaigns, in the worker, a moment after the exchange is noted (ADR 0145). So `get-next-recipient`, asked right after a sending is noted, gives the same recipient, and the prompt stops the AI.

## Decision

- **The exchange noted first joins the campaign.** Noted once by an AI (`noteOnce`), a sending whose fingerprint names an exchange still kept is checked first: when that exchange is of the same contact, outbound on the campaign's channel and of no campaign, it joins the campaign (`Exchange::joinCampaign()`, the event `ExchangeJoinedCampaign`), the recipient still to handle. Its facts then name the campaign, and the reaction of Campaigns processes the recipient as for any exchange of a campaign. The text kept is the imported one. Any other exchange of that fingerprint stays « already noted », and an erased one « erased before ».
- **Who may do it.** Any prospector of the account with the right to manage personal data may, which is the access of `log-sent-message`. Joining the campaign counts as a correction by the AI's member (who corrected it last).
- **The answer names the exchange the sending was noted as.** The command still returns nothing. The query `SendingNoted` takes what the tool was given (the member, the campaign, the contact, the Message-ID or the LinkedIn conversation, the moment, the text). The core fingerprints the sending as the command does (`RecipientInCampaign::fingerprintOfSending()`), then reads the registered fingerprint. The tool answers `noted` with that exchange.
- **The next recipient does not wait for the reaction.** `NextRecipient` asks Prospecting which of the campaign's pending recipients already have an exchange of the campaign, through the new published service `WhichContactsHaveExchangesOfCampaignService` and the Campaigns port `CampaignExchanges`. The next recipient and the count left pass over them. With nobody else left, the campaign is refused as no longer led, as it will be once the reaction ends it. The repository answers the question (`ExchangeRepository::contactsWithAnExchangeOf()`), reading each contact's timeline without restoring the exchanges.

## Consequences

- An AI leads a campaign to its end with the import of Gmail active, which is the normal case, and with the worker a moment behind.
- `get-next-recipient` reads a little more: one query of a timeline for each pending recipient.
- A dismissal is a command of Campaigns itself: the next recipient follows it at once, without Prospecting.
- An e-mail imported first now names the campaign in its timeline, as if the AI had noted it.

## Alternatives considered

The maintainer weighed them on the plan of the ticket.
- The sending imported first: the import of Gmail attaching the e-mail itself to the campaign of a pending recipient. It would change the import (ADR 0154) and guess the campaign when several run. Or the API sending Campaigns a command that processes the recipient on « already noted »: two contexts in one call, against ADR 0145, and the exchange would never name its campaign.
- The next recipient: processing the recipient in the same unit as the exchange, two aggregates of two contexts (against ADR 0042 and 0145); running the reaction inline for this tool, which changes the messaging (ADR 0101); or changing only the prompt, so that the AI waits and asks again.

## Revisit when

Campaigns keeps its recipients' exchanges itself, or Prospecting indexes the exchanges by campaign.
