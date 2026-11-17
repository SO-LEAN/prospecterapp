# 0139. The LinkedIn profile is a person's own, kept in one form

- Date: 2026-11-17
- Type: domain
- Status: Accepted
- Implemented by: `packages/core/src/PersonalData` (`LinkedInProfile`, `InvalidLinkedInProfile`, `Person::changeContactDetails()`, `Person::collectAsContact()`, `Masking::linkedInProfile()`), `features/noter-le-profil-linkedin-d-un-contact.feature`, `features/changer-mes-coordonnees.feature`, `apps/api/src/PersonalData` (`PersonContactDetailsInput`, `PersonContactDetailsResource`, `PersonDocument`, `PersonCards`), `apps/front/src/shared/forms/person-contact-details.tsx`, `apps/front/src/shared/ui/linkedin-profile.tsx`

## Context

A prospector notes the address of the LinkedIn profile of a contact, opens it from their card, and later targets the contacts reachable on LinkedIn; a member may note their own. The contact details of the shared kernel (`ContactDetails`: an email, a phone number, a language) are shared by the persons, the organizations and the accounts. The ticket left open whether the profile goes there, or to the person only. A profile is typed in many forms (with or without its scheme, a country subdomain, a trailing slash, the query of a share), and the page of a company on LinkedIn (`/company/`) is no profile of a person.

## Decision

- The LinkedIn profile is a value object of PersonalData (`LinkedInProfile`), held by the person beside their contact details, noted with them: on collecting a contact and on changing their contact details, never required.
- It is kept in one form, `https://www.linkedin.com/in/<identifier>`, the identifier in lower case, without what follows it; an address that is no profile of a person is refused on its field (`InvalidLinkedInProfile`).
- Read masked, it is a fixed number of stars, as the rest of the contact details (0031).

## Consequences

- The organizations and the accounts have no LinkedIn profile; their contact details stay those of the shared kernel. The API has an input and a resource of the contact details of a person of their own, the three fields of the shared ones and the profile; a change of the constraints of the email or the phone number goes in both.
- One profile has one value, whatever was typed: a context counting the contacts reachable on LinkedIn asks whether a person has one (`Person::linkedInProfile()` not null), not its form.
- A person saved before has none.
- A raw non-ASCII identifier and its percent-encoded form are two values for one profile.

## Alternatives considered

The profile in the shared `ContactDetails`, named by the ticket: refused, since the organizations and the accounts share them and the page of a company is no profile of a person; the page of an organization is out of the scope of the ticket.

## Revisit when

The organizations get their LinkedIn page, or a percent-encoded identifier is found noted twice.
