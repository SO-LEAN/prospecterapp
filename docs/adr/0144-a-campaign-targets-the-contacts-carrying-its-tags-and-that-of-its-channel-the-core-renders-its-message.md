# 0144. A campaign targets the contacts carrying its tags and that of its channel, the core renders its message

- Date: 2026-11-17
- Type: domain
- Status: Accepted
- Supersedes: for the campaigns of an account, the rule of [0049](0049-lists-in-slices-after-a-cursor.md) that a list is sliced and never counted
- Implemented by: `packages/core/src/Campaigns` (`Campaign`, `Targeting`, `MessageTemplate`, `CampaignPossibilities`, `CampaignTargets`, `PrepareCampaign`, `ReviseCampaign`, `DiscardCampaign`, `ListCampaigns`, `ConsultCampaign`, `RenderCampaignMessage`), `packages/core/src/Prospecting/Contract/Service/NameOrganizationsOfContacts`, `packages/core/features/preparer-une-campagne.feature`, `apps/api/src/Campaigns/Persistence/DynamoCampaignRepository.php`, `apps/api/src/Campaigning`, `apps/front/src/features/campaigns`

## Context

A member prepares a campaign of communication: a name, a channel (e-mail, call, SMS or LinkedIn), the tags of the contacts it targets and a message the application reminds them for each contact. Prospecter sends nothing: the member contacts each person themselves. The epic goes on with a list frozen and changed by hand, a campaign started, its exchanges, then ended or stopped; this record sets the life of a campaign for all of it, the first ticket only preparing drafts. A future client (an MCP server) reads the campaigns as the screen does. The contacts are found by tags since ADR 0143.

## Decision

- A campaign is an aggregate of Campaigns, shared by the team of its account, prepared by its author: its name (1 to 100 characters), its channel, the tags chosen, its message template (5,000 characters at most, and the subject of an e-mail, none for another channel).
- Its life: draft, then frozen, started, and ended or stopped. A draft is revised whole and discarded; the other states come with their tickets.
- Each channel needs the means of reaching a contact its automatic tag says: #EMAIL, #PHONE (a call), #SMS, #LINKEDIN. A campaign targets the contacts carrying every tag chosen and the tag of its channel, all of them counting in the 5 tags of a search (ADR 0143): 4 tags chosen besides it, 5 when one of them is the tag of the channel.
- In draft, the target is not kept: it is the search by its targeted tags as the contacts carry them now, so it follows their tags and their contact details. The list frozen by the next ticket is kept then.
- The core renders the message for a contact: `{prénom}`, `{nom}`, `{organisation}` (the first organization Prospecting lists for the contact) and `{fonction}` replaced, a variable without value by nothing, any other text kept; the subject the same way. The names and the job title are read by PersonalData as the member reads them, the organization by Prospecting, through their published services. The screen previews the message of the draft saved, rendered by the core, never by itself.
- The campaigns of an account are listed whole, the newest first, each with the contacts it targets counted: the campaigns, then the tags of every contact derived once from the partition of the account (ADR 0143).
- Only a member with the right to manage personal data runs campaigns, every gesture and every reading: a campaign is run on the contact details of the contacts. The right is a role on the controllers, a campaign of another account unknown to its voter (ADR 0081).

## Consequences

- A draft costs nothing when the tags of the contacts change: nothing to update, its target is read.
- Listing the campaigns reads the partition of the account once, whatever their number; an account has tens of campaigns.
- The preview of a message being typed waits for the draft to be saved.
- Renaming or adding a variable of the message is a change of the core only, every client rendering it through it.

## Alternatives considered

- The screen rendering the preview of the message being typed itself, mirroring the rule of the core (proposed by the plan of the ticket): the live preview, but a rule of the domain written twice, in the core and in the front; refused at the review of the plan, the front only presenting.
- The core refusing a member without the right to manage personal data (proposed by the plan of the ticket, as PersonalData refuses its readers): refused at the review of the plan, the right being a role on the controllers since ADR 0081.
- On the number of tags (weighed at the review of the plan): 4 tags chosen at most whatever the channel, a refusal of its own; or a search of 6 tags for a campaign, changing the rule of ADR 0143. The tag of the channel counting in the 5 keeps one rule for every search.

## Revisit when

A campaign needs more than 5 tags at once, or its preview must follow the message as it is typed.
