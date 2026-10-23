# 0074. An exchange with a contact is an aggregate of Prospecting, kept for the organizations of the contact

- Date: 2026-10-18
- Status: Accepted, the interlocutor of an exchange and the links to the organizations superseded by [0076](0076-the-timeline-of-an-organization-gathers-its-exchanges-and-those-of-its-contacts.md), the right to read the exchanges by [0078](0078-every-prospector-reads-the-exchanges-and-corrects-their-text.md), the exchanges of a forgotten contact by [0079](0079-the-exchanges-of-a-forgotten-contact-stay-to-make-conform.md)
- Supersedes: for a timeline, the order and the cursor of [0049](0049-lists-in-slices-after-a-cursor.md); for the text of an exchange, the single place of the personal data of [0031](0031-personal-data-context.md)
- Implemented by: `packages/core/src/Prospecting/Domain/{Exchange,ExchangeFacts,ExchangeRepository,ContactOrganizations}.php`, `packages/core/src/Prospecting/Application/{NoteExchange,ConsultTimeline,StopKeepingExchanges}`, `packages/core/features/noter-un-echange.feature`, `apps/api/src/Prospecting/Persistence/DynamoExchangeRepository.php`, `apps/api/src/Prospecting/Controller/{NoteExchange,ShowTimeline}.php`, `apps/front/src/features/exchanges`

## Context

A prospector wants to find on the card of a contact everything that was said with them: calls, text messages, emails, LinkedIn messages, meetings, and to note a new one in a few seconds. Such an exchange is a fact of the real world, not a change made in the application: the domain events and the activity journal (0043) keep the changes, by identities only. The text of an exchange may hold personal data, and the contacts are kept by PersonalData (0031, 0041), which knows nothing of the organizations prospected.

## Decision

- An `Exchange` is an aggregate of Prospecting: the account, who noted it, the `PersonId` of the contact, the organizations it is kept for, and its facts (`ExchangeFacts`: a `Channel`, a `Direction`, whether a call was missed, the moment it happened, a free text). Only a call may be missed; an exchange is never in the future; noting or reading the exchanges needs the right to manage personal data.
- It is kept for the organizations of the account the contact is part of when it is noted (`ContactOrganizations`, a person none of them names being no contact of the account), and lives as long as one of them is prospected: when the account stops prospecting an organization, `StopKeepingExchanges` reacts to `ProspectingStopped` and erases the exchanges kept for no other one. Its lifetime follows the organizations, not the contact.
- The timeline of a contact is ordered by the moment of the exchanges, the most recent first, not by their identity: an exchange of last week may be noted today. Its cursor is the moment and the identity of the last exchange of a slice, the rest of 0049 unchanged (20 by slice, `next`). Noting is an intent of the timeline (`TimelineIntent`), the link `noteExchange`.
- In the table of Prospecting (0048): the item `EXCHANGE#<id>` / `EXCHANGE`, listed in `GSI1` by `TIMELINE#<account>#<person>` under its moment and identity, and the links `EXCHANGES#<organization>` / `EXCHANGE#<id>`, written in the same transaction, finding the exchanges kept for an organization.

## Consequences

- Prospecting keeps a text that may hold personal data, against the rule that PersonalData alone keeps them: the forgetting of a contact (GDPR) leaves its exchanges while one of their organizations is prospected; the ticket of the erased contacts is to make it conform.
- The timeline of an organization, the detail, the correction, the attachments and the reading without the right will build on this aggregate.

## Alternatives considered

- The activity journal as the history of the exchanges: it keeps what changed in the application, by identities only, and nothing of what was said.

## Revisit when

- A contact is forgotten while its exchanges stay, or the timeline must show the exchanges of an organization with itself.
