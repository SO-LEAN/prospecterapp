# 0076. The timeline of an organization gathers its exchanges and those of its contacts

- Date: 2026-10-20
- Status: Accepted
- Supersedes: for the interlocutor of an exchange and the links to the organizations, [0074](0074-an-exchange-with-a-contact-is-an-aggregate-of-prospecting.md)
- Implemented by: `packages/core/src/Prospecting/Domain/{Exchange,Organization}.php` (`notedWithTheOrganization`, `ensureHasContact`, `timelineIntentsOf`), `packages/core/src/Prospecting/Application/{NoteOrganizationExchange,ConsultOrganizationTimeline}`, `packages/core/features/consulter-la-timeline-d-une-organisation.feature`, `apps/api/src/Prospecting/Persistence/DynamoExchangeRepository.php`, `apps/api/src/Contacts/{NoteOrganizationExchange,ShowOrganizationTimeline,OrganizationTimelineReader}.php`, `apps/front/src/features/exchanges`

## Context

A prospector wants to see on the card of an organization everything said with it: with each of its contacts, and with the organization itself (its switchboard, its generic email), which is nobody's contact. An exchange of 0074 is always with a contact, and the links `EXCHANGES#<organization>` / `EXCHANGE#<id>` find the exchanges kept for an organization in no order, enough to erase them, not to read them a slice after the other.

## Decision

- The interlocutor of an `Exchange` is a contact (`PersonId`) or, with none, the organization itself: such an exchange is kept for that organization alone, and is in the timeline of no contact.
- From the card of an organization, `NoteOrganizationExchange` notes an exchange with the organization itself, or with one of its contacts (`NotAContact` otherwise), kept like any other for every organization the contact is part of.
- The timeline of an organization (`ConsultOrganizationTimeline`) is the exchanges kept for it, ordered and sliced like the timeline of a contact (0074), with the right to manage personal data. Prospecting answers the `personId` of each exchange; the API reads the names of the persons of a slice from PersonalData in one call (`ConsultPersons`), the organization being named by its card.
- The card leads to its timeline by links (0027): `OrganizationResponse` carries the `timelineIntents` the organization offers the prospector (`Organization::timelineIntentsOf()`), not new `OrganizationIntent`s: noting an exchange changes nothing of the organization, and records no event of it.
- In the table of Prospecting (0048): each link `EXCHANGES#<organization>` / `EXCHANGE#<id>` is also listed in `GSI1` by `ORGANIZATION_TIMELINE#<organization>`, under the position of the exchange (its moment and its id, the cursor of a slice); the exchanges of a slice are then read by their keys. An exchange with the organization itself has no `GSI1` key of its own: it is in no timeline of a contact.

## Consequences

- An exchange keeps the organizations of its contact when it was noted: a contact who joins an organization later does not bring their former exchanges into its timeline.
- The links written before this decision, only on the dev instances (the exchanges never reached a release), are in no timeline of an organization until their exchange is written again.

## Alternatives considered

None: the ticket asked for one timeline with the interlocutor of each exchange.

## Revisit when

- The timeline must be filtered by channel or by contact, or show the contacts removed or erased.
