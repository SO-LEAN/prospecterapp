# 0159. A LinkedIn profile designates one contact of the account, its former ones still recognize them

- Date: 2026-11-18
- Type: domain
- Status: Accepted
- Implemented by: `packages/core/src/PersonalData` (`Person::linkedInProfilesHeld()`, `Person::holdsLinkedInProfile()`, `LinkedInProfileUniqueness`, `LinkedInProfileAlreadyUsed`, `LinkedInProfileTakenMeanwhile`, `PersonRepository::contactHoldingLinkedInProfile()`), `features/noter-le-profil-linkedin-d-un-contact.feature`, `apps/api/src/PersonalData/Persistence/{DynamoPersonRepository,PersonDocument}.php`, `apps/api/src/Consistency/LinkedInProfilesOfTheContacts.php`, `apps/front/src/shared/forms/person-contact-details.tsx`

## Context

The LinkedIn profile (0139) is the surest key to recognize a person come from LinkedIn, even without their email; the email is already unique among the persons an account keeps for a purpose (0039). A person may rename the address of their profile, and contacts noted before this decision may already share a profile. Forgetting the contacts an organization leaves alone runs in one transaction of 100 writes at most, each profile a contact holds costing one more.

## Decision

- A LinkedIn profile designates one contact of an account, by their profile or a former one: a domain service, `LinkedInProfileUniqueness`, refuses a profile another contact holds when a contact is collected or their contact details change, naming that contact (`LinkedInProfileAlreadyUsed`, its context `holder` and `holderId`). Another account may note it; the profile of a member is not concerned.
- A person keeps their last three former profiles, the most recent first: personal data, never shown nor published, forgotten with the person. Noting back a former one makes it the current one again.
- A contact keeps a profile they hold already: the duplicates noted before the rule block nothing; `app:consistency:check` lists them by the ids of the contacts, for the members to merge them or correct one of them by hand.
- The safety net (0039, 0048): the reservation of each profile a contact holds, written in the transaction of the person under the condition that no other contact holds it (`LinkedInProfileTakenMeanwhile`, a refusal of its own since it knows no holder), deleted only by its owner; `app:keys:rebuild` reserves the profiles of the contacts stored before, one two contacts share for the oldest of them.

## Consequences

- A contact forgotten costs seven writes at most: fourteen contacts in the transaction forgetting those an organization leaves alone, down from some thirty-three.
- An address older than the last three no longer recognizes the contact, and may be noted on another one.
- The oldest of two duplicates erased, its reservation goes with it and the younger has none: a third contact may take the profile until `app:keys:rebuild` runs again, `app:consistency:check` then listing them.
- `app:keys:rebuild` runs once on each instance for the contacts stored before.
- The fusion of two contacts stays to do; until then, the members correct the duplicates by hand.

## Alternatives considered

The maintainer weighed:
- keeping every former profile: every address still recognizes the contact, but no number of contacts forgotten together fits a transaction for sure;
- the uniqueness over the members' own profiles too, as the email: the ticket speaks of the contacts only, and a refusal would name a colleague who has no sheet to link.

## Revisit when

The contacts can be merged, or an organization leaves more than fourteen contacts alone at once.
