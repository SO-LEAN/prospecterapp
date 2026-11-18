# 0146. A contact to attach waits without organization, held by the member who meets them, erased 30 days after their last meeting

- Date: 2026-11-18
- Type: domain
- Status: Accepted
- Supersedes: for « a contact is never alone », [0042](0042-contacts-never-alone-domain-events.md); for « an exchange is kept for the organizations its contact was part of », [0074](0074-an-exchange-with-a-contact-is-an-aggregate-of-prospecting.md), and its to-dos alike ([0113](0113-a-to-do-is-an-aggregate-of-prospecting-erased-with-its-contact.md))
- Implemented by: `packages/core/src/Prospecting/Domain/Model/ContactToAttach.php`, `packages/core/src/Prospecting/Domain/Service/ContactOrganizations.php`, `packages/core/src/Prospecting/Application/{EraseContactLeftUnattached,ProposeOrganizationToAttach,ConsultContactToAttach}`, `packages/core/src/PersonalData/Domain/Model/Person.php` (`collectToAttach`), `packages/core/features/les-contacts-a-rattacher.feature`, `apps/front/src/features/contacts-to-attach`

## Context

A person who books a meeting by the public link of a member (a later ticket) says who they are, not for which organization they work. Until now a contact belonged to at least one organization, was erased as soon as none named them (ADR 0041, 0042), and their exchanges and to-dos were kept for their organizations (ADR 0074, 0113). Such a person must be kept in the meantime, be seen until a member attaches them, and not be kept for ever if nobody does: personal data are kept only as long as their purpose needs them.

## Decision

- A **contact to attach** is a contact of the account named by no organization, held by the member who meets them (`ContactToAttach` of Prospecting, identified by the person). PersonalData collects them without organization (`Person::collectToAttach`), for the prospecting of the account, by a member with the right to manage personal data, the same right as for any contact; their email stays unique among the contacts.
- While it waits, its exchanges and its to-dos are kept for no organization, held by their holder; only its holder plans, notes or notes a to-do with it (`ContactToAttachOfAnother` otherwise). Its page and its timeline are read like those of any contact of the account; only its holder sees it among what they have to process, with the day it will be erased.
- Attaching it is adding it to an organization (`AddContact`): its exchanges and its to-dos are then kept for that organization, and it is no longer to attach.
- The organization proposed to attach it is the one of the account whose email has the domain of its email; a domain of a public mailbox (gmail.com, orange.fr…) proposes none.
- Never attached, it is erased from the start of the 30th day after its last meeting, on the clock that meeting was given in (kept with it); each new meeting pushes that day back. Its person is forgotten by its holder, its exchanges and its to-dos erased with it: none was kept for an organization, nothing to make conform.

## Consequences

- Only a member with the right to manage personal data holds a contact to attach, and erases it: a booking by the link of a member without that right is refused, unless the right of the booking is decided otherwise then. A holder who lost the right meanwhile leaves the erasure in the failures, which the check of the consistency reports.
- A contact to attach counts among the contacts of the account in its figures and carries its tags like any contact: a campaign may target them.
- The erasure needs a task run on a schedule, without a member at the origin (ADR 0147).
- The alerts before the erasure are read by the SPA from what is to process, nothing is sent.

## Alternatives considered

- A purpose of its own in PersonalData (`Purpose`), suggested by the ticket: the email of a person would no longer be unique between a contact and a contact to attach, and the purpose would change at the attachment. A contact to attach stays a contact of the account; Prospecting, which knows its meetings, keeps what makes it one.
- The day of the erasure read on a fixed clock, of the platform or UTC, weighed at the review of the plan: simpler, but a meeting near midnight would be erased a day off. The clock of the meeting is kept with the contact.

## Revisit when

A contact to attach is created otherwise than by a meeting, or a team shares its contacts to attach.
