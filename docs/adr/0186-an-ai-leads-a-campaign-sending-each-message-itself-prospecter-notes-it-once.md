# 0186. An AI leads a campaign, sending each message itself, Prospecter noting it once

- Date: 2026-11-21
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/src/Campaigns/Domain/Model/{MessageMode,MessageTemplate,Campaign,Recipients}.php`, `packages/core/src/Campaigns/Application/NextRecipient`, `packages/core/src/Prospecting/Application/NoteCampaignExchange`, `packages/core/src/Prospecting/Domain/Service/NotedOnce.php`, `packages/core/features/{preparer-une-campagne,mener-une-campagne-avec-une-ia}.feature`, `apps/api/src/Campaigning/{NextRecipientReader,Mcp/NextRecipientTool,Mcp/LeadCampaignPrompt}.php`, `apps/api/src/Prospecting/Mcp/NoteCampaignExchangeTool.php`, `apps/front/src/features/campaigns/components/campaign-form.tsx`

## Context

A member leads a campaign by hand, recipient after recipient (0145). They want an AI to lead it for them: take each recipient, send the e-mail by their mailbox or the LinkedIn message by the extension of Claude in Chrome, then note in Prospecter what it sent, the recipient then processed. Prospecter still sends nothing (0144). The member chooses whether the AI sends the message as rendered for each person or rewrites it for them, keeping its idea. An AI may note the same sending twice; the first noting processes the recipient, through a reaction, so that the second would be refused as « already processed ». The noting of the exchange of a recipient was the last write of a member without its tool (0163, 0183).

## Decision

- **The mode of the message** (`MessageMode`, `asIs` by default, or `adapted`) is part of the message of the campaign (`MessageTemplate`): prepared and revised with it, until the campaign starts; any other mode refused (`UnknownMessageMode`). A campaign stored before it reads as is.
- **The next recipient is a query of Campaigns**, `NextRecipient`: the first recipient still to handle, in the order added, its message rendered for them, the mode, and how many are left; refused once the campaign is no longer led (`CampaignNotRunning`). It restores the aggregate by its repository, as `RenderCampaignMessage` does: the next recipient and the rendering are rules of the aggregate. This is an exception to « a query reads its read model through a port ». Asked again at each step, a recipient handled on the screen meanwhile is no longer given.
- **The MCP tool `destinataire-suivant` has no endpoint**, as `identifier` (0173): no screen asks it. Its access is that of the message of a campaign. The API composes it as it composes the recipients: PersonalData reads how the recipient is reached, Prospecting and Campaigns their organizations and tags. The API words in English, from the mode, what the AI does with the message.
- **The sending is noted by the tool `noter-l-envoi`**, adapting `api_campaigns_note_exchange`, idempotent, answered as any tool noting an exchange (0166). The exchange of a recipient is fingerprinted as any noted exchange, by the `Message-ID` of an e-mail or the conversation of a LinkedIn message, which the endpoint takes too. **Noted once, the fingerprint is checked before the recipient** (`NotedOnce::ensureFirstAs`), so that the same sending noted again is « already noted », whatever the reaction did meanwhile. Noted by hand, it is never refused as a duplicate.
- **The MCP prompt `mener-une-campagne`** gives the AI the steps: find the campaign, take the next recipient, send by its own tools as the instruction says, note it, dismiss whom it cannot reach once its member confirmed, again until the campaign is no longer led.

## Consequences

- The member leads a campaign by one sentence to their AI, by hand and by the AI at the same time.
- A query restores an aggregate once more (0177 named the same exception); the maintainer is not keen on it, to review with that one.
- **A gap left open**: when the import of Gmail notes the e-mail the AI sent before the AI notes it (0154), the noting by `noter-l-envoi` answers « already noted »: the recipient stays to handle, and `destinataire-suivant` gives them again. The prompt tells the AI never to send a second message to a recipient, and to stop and say who; attaching the exchange noted first to the campaign is left to the maintainer.
- A daily cap of the sendings, to come, will change the prompt.
- The calls and the answers received stay out.

## Alternatives considered

- An endpoint beside `destinataire-suivant`: no screen needs it, the screen of a campaign listing its recipients.
- A query reading the read model of the campaign, the message rendered again from it: not kept, the rendering being a rule of the aggregate that `RenderCampaignMessage` already asks.

## Revisit when

A screen leads a campaign one recipient at a time, the import of Gmail notes the e-mails of a campaign before its AI, or the queries restoring an aggregate are replaced.
