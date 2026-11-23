# 0199. A booker is recognized by their e-mail only, otherwise they become a new contact to attach

- Date: 2026-11-23
- Type: domain
- Status: Accepted
- Supersedes: for the booker recognized by their phone, [0164](0164-a-slot-booked-by-a-public-link-becomes-a-planned-exchange-of-its-host-its-booker-identified-by-personaldata.md)
- Implemented by: `IdentifyBookerHandler`, `PersonRepository`, `DynamoPersonRepository`, `features/reserver-un-creneau.feature`, `features/inviter-qui-me-reserve-par-mon-agenda-google.feature`, `docs/domain/personal-data.md`, `docs/domain/booking.md`

## Context

ADR 0164 identifies a booker as the account's contact who has their e-mail, otherwise one who has their phone, otherwise a new contact to attach. The event in the host's calendar then invites the contact's e-mail, under the contact's name (ADR 0175).

Some bookers typed an e-mail that no contact has, and the phone of a contact who has another e-mail. They were attached to that contact. The confirmation page announced an invitation to the typed address, but the invitation went to the contact's address. The booker received neither the invitation nor the video call's link. The host saw the appointment under another person's name. This happens to someone booking with a new address, and to two persons sharing a number (#271).

## Decision

- A booker is the account's contact who has their e-mail, whatever its case.
- Otherwise, a new contact to attach is collected for the host from what the booker typed: their name, e-mail and phone. This holds even when the phone is a contact's.
- So the invitation always goes to the address the booker typed, the one the confirmation page announces.
- This rule covers booking only. Identifying a person elsewhere (`identify`, the imports) does not change.

## Consequences

- A contact who books with a new address becomes a second contact, sharing their phone. The member merges or erases it like any duplicate.
- The appointments booked before this change keep the contact they were attached to.
- The persons' port no longer looks a contact up by phone: identifying a booker was its only user.

## Alternatives considered

The maintainer weighed three options:

- Keep attaching by phone, and invite only the typed address. The host would still see the appointment under another person's name. It lost.
- Attach by phone, and complete the contact with the typed address. Completing a contact fills only its empty fields (ADR 0177), so a contact who has an e-mail would keep it. It lost.
- Stop attaching by phone, and create a contact to attach. It was chosen.

## Revisit when

Members report many duplicate contacts that bookers created with a second address.
