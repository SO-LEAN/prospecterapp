# 0129. A prospector without the right to manage personal data reads the names of the contacts and plans their own exchanges

- Date: 2026-11-16
- Type: domain
- Status: Accepted
- Supersedes: for the name of a contact and searching the contacts by their name, the masking and the refusal of [0031](0031-personal-data-context.md) for a member without the right; for planning an exchange, the right to manage personal data noting one needs of [0078](0078-every-prospector-reads-the-exchanges-and-corrects-their-text.md)
- Implemented by: `packages/core/src/PersonalData` (`PersonReading::ofTheName()`, `Reader::searchesTheEmails()`), `features/consulter-une-personne.feature`, `features/rechercher-mes-contacts.feature`, `features/planifier-un-echange.feature`, `features/traiter-un-echange-planifie.feature`, `features/consulter-mon-calendrier.feature`, `apps/api/src/{Prospecting,Contacts}` (`PlanExchangeController`, `PlanOrganizationExchangeController`, `AgendaReader`, `TimelineResourceFactory`), `apps/front/src/features/exchanges`

## Context

An assistant, a prospector without the right to manage personal data, reads the exchanges (0078) but cannot plan one, and reads the names of the contacts masked (0031): their calendar tells them neither with whom they have an exchange, nor lets them plan the next one. Planning with a contact needs finding them, by their name.

## Decision

- Every prospector of the account reads the first and last name of each contact of the account in clear, wherever a contact is read: the search, the lists, the card, the timelines, the calendar, the day, the event pushed into their Google calendar.
- Without the right, the contacts are searched by the beginning of their name only, never by their email; recognizing a caller by their number still needs it.
- The job title, the email, the phone number and the picture of a contact stay masked without the right; the detail of an exchange gives no number to call. The text of an exchange stays flagged (0078). The profiles of the members, no contacts, stay masked.
- Every prospector plans a call, a meeting or a video call, with a contact or with the organization itself, and handles the exchanges they hold, as they already did (0112). Noting an exchange that took place, correcting it as a whole, erasing it and its files still need the right.

## Consequences

- A name read in clear by an assistant is a personal datum of the contact: the account answers for it as for any reading of its members.
- The `masked` of a card says that its contact details are masked, its name being in clear for a contact.

## Alternatives considered

The names in clear only in the calendar of the assistant and the detail of their exchanges, masked elsewhere: refused by Michel, the search by name giving them anyway.

## Revisit when

The assistant goes away as a role, or a contact must be hidden by name from some members.
