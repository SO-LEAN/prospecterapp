# 0042. A contact is never alone; domain events recorded by the aggregates, on a synchronous event bus

- Date: 2026-10-15
- Status: Accepted
- Supersedes: the two separate gestures and the erasure of [0041](0041-contacts-of-prospected-organizations.md)
- Implemented by: `packages/core/src/SharedKernel/Domain/{RecordsEvents.php,Event}`, `SharedKernel\Application\{EventHandler,Port\Events}`, `Prospecting\Domain\OrphanContacts`, `Prospecting\Application\StopProspecting`, `apps/api/src/Shared/MessengerEvents.php`, `apps/api/config/packages/messenger.yaml`

## Context

With 0041, a contact was collected alone in a Contacts screen, then attached; removing it from its organizations or erasing it left orphans or names behind. The user found the two steps poor and set the rule: a contact never exists without an organization, a contact is collected from an organization by a use case taking a list of organizations, an existing contact may still be attached, and an organization that stops being prospected takes its contacts with it when no other organization names them. Each of these gestures spans PersonalData and Prospecting.

## Decision

- Domain events: an aggregate records what happened to it in its intention methods (`RecordsEvents`); the use case saves it, releases its events and publishes them on the port `Events`. The events crossing contexts are the published language of the shared kernel (`ContactCollected`, `ContactsLeftWithoutOrganization`, `ContactForgotten`), carrying the member who did the gesture. Another context reacts with an `EventHandler` acting as that member, through its own intention methods.
- The API delivers them on a synchronous Messenger `event.bus`, within the use case: a refusal of a reacting context refuses the whole gesture.
- `CollectContact` takes `organizationIds` (at least one), checks them through the port `ProspectedOrganizations`, then the person records `ContactCollected` and Prospecting attaches it.
- Removing a contact from its last organization, or stopping to prospect an organization (`StopProspecting`, a new use case), records `ContactsLeftWithoutOrganization` for the contacts no other organization names (`OrphanContacts`, a domain service passed to the intention method); PersonalData erases them. Stopping to prospect detaches the subsidiaries, and needs the right to manage personal data when the organization has contacts.
- Erasing a contact records `ContactForgotten`; Prospecting detaches it from every organization.
- `OrganizationId` becomes a published identity of the shared kernel; the query `ConsultOrganizationsOfContacts` lets the contact list show the organizations of each contact.

## Consequences

- The rule holds whatever the gesture: no orphan contact, no organization naming an erased person.
- A gesture now changes several aggregates, in two databases (0038), without a transaction: every check runs before the first change, but a failure of the infrastructure between two saves may still leave a gap.
- The event bus the GDPR step needed exists.
- The in-memory doubles must not keep the recorded events with the stored state, like the Doctrine mapping.

## Alternatives considered

- Direct calls between contexts through ports: the user chose events.
- Refusing to remove the last organization of a contact: the user chose to erase it.
- Refusing to stop prospecting an organization with subsidiaries: the user chose to detach them.
- Events published by the use cases rather than recorded by the aggregates (the first version of this record): the user wanted the aggregates to record them and the use cases to release them.

## Revisit when

- A reaction must not refuse the gesture that caused it, or must run later: the event bus gets a transport, and the events an outbox.
