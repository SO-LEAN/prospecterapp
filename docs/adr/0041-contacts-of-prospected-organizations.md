# 0041. Contacts: the persons in PersonalData, their identities in the organization, two separate gestures

- Date: 2026-10-15
- Status: Accepted; the two separate gestures and the erasure leaving names behind superseded by [0042](0042-contacts-never-alone-domain-events.md)
- Implemented by: `packages/core/src/PersonalData/Application/{CollectContact,ChangeJobTitle,ForgetContact,SearchContacts}`, `packages/core/src/Prospecting/Application/{AddContact,RemoveContact}`, `Prospecting\Domain\KnownContacts`, `apps/api/src/OrganizationContacts`, `apps/front/src/features/contacts`

## Context

A prospected organization had its general contact details only; the persons prospected in it are personal data, which PersonalData keeps by account and purpose (0031), in clear for a member with the right to manage personal data and masked for the others. Prospecting may keep a `PersonId`, never a copy.

## Decision

- A contact is a `Person` of PersonalData with the purpose `Prospecting`, with a job title (personal data too, masked like the rest). Collecting, changing, searching by text and forgetting it need the right to manage personal data; without it, the contacts are read masked and no action is offered (`ContactsIntent`, the links of the API).
- Two separate gestures: a person is collected in a Contacts screen, then attached to an organization; one person may be the contact of several organizations. `Organization` keeps the list of its contacts' `PersonId`s (`AddContact`, `RemoveContact`, with the same right) and asks the port `KnownContacts`, answered from PersonalData by an anticorruption adapter, whether the person is one the account prospects.
- Removing a contact detaches it; erasing the person is a gesture of PersonalData. The organizations that still name an erased person leave them out when they are read (`GET /api/organizations/{id}/contacts` composes both contexts and skips the persons PersonalData no longer knows).

## Consequences

- No command spans two contexts; each gesture is one aggregate, as 0030 requires.
- An erased person stays named by the organizations until the cleaning by domain event planned with the GDPR step.
- The screen of an organization reads its contacts with a second request (the composition), one batch call to PersonalData.

## Alternatives considered

- The API chaining two commands (collect then attach, with a compensation on failure), or a domain event creating the person when Prospecting adds a contact: the user preferred two separate gestures.
- The job title kept by Prospecting with the contact: the user kept it in PersonalData, as personal data.
- Removing a contact erasing the person in the same gesture: the user kept two gestures.

## Revisit when

- The GDPR step brings domain events: an erased person should then be removed from the organizations naming them.
