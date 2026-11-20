# 0170. A contact resembling another of their organization by their names is suspected of being their duplicate, without being refused

- Date: 2026-11-20
- Type: domain
- Status: Accepted
- Implemented by: `packages/core/src/PersonalData/Domain/Model/{PersonResemblanceKey,Person,PersonIntent}.php`, `packages/core/src/PersonalData/Domain/Service/DuplicateSuspicion.php`, `packages/core/src/PersonalData/Domain/Port/OrganizationsOfContacts.php`, `packages/core/src/PersonalData/Domain/Policy/PersonReading.php`, `packages/core/src/PersonalData/Application/{CollectContact,RenamePerson,DismissPossibleDuplicate,SuspectDuplicateInReturn,LiftSuspicionInReturn,DismissSuspicionInReturn}`, `packages/core/src/Prospecting/Contract/Service/WhichOrganizationsNameTheseContacts`, `packages/core/features/{soupconner-un-doublon-de-contact,dire-qu-un-contact-n-est-pas-un-doublon}.feature`, `apps/api/src/PersonalData/Persistence/DynamoPersonRepository.php`, `apps/api/src/PersonalData/AntiCorruption/ProspectingOrganizationsOfContacts.php`, `apps/api/src/Contacts/Controller/DismissPossibleDuplicateOfContactController.php`, `apps/front/src/features/contacts/components/possible-duplicates.tsx`

## Context

The email and the links refuse the true duplicates of a contact (0039, 0159, 0165), but none is required: a person noted twice by a member, or by an AI working for them, under a name written otherwise (« Ada Lovelace » and « ada  LOVELACE » at Acme), is not found. A resemblance is no proof: two people may share a name, so it cannot refuse. The organizations follow the same mechanism (0162). PersonalData keeps the names, Prospecting where a contact works.

## Decision

- **The resemblance key** (`PersonResemblanceKey`, of the domain): the first name and the last name, each lower case, without accents nor what is no letter nor digit, spaces included (`SearchKey`). No key without both (a last name alone resembles too many), none for the profile of a member: only a contact has one. Personal data, it is kept with the person and forgotten with them.
- **In the same organization only** (the maintainer): the contacts of the account sharing the key are kept when one of the organizations naming them names the contact too. PersonalData does not keep where a contact works: it asks Prospecting, through its port `OrganizationsOfContacts` and the published service `WhichOrganizationsNameTheseContactsService`, once for the contact and all those resembling them. At their collection, the organizations of the contact are those the command names, Prospecting attaching them only by its reaction to `ContactCollected`.
- **When**: collected as a contact (`CollectContact`) or renamed (`RenamePerson`), a contact is suspected of being a duplicate of each contact resembling them (`DuplicateSuspicion`, a domain service read before `save()`), and no longer of those they no longer resemble; they record `DuplicateSuspected` or `DuplicateSuspicionLifted` on themselves only, and a reaction suspects or lifts the other in return (`SuspectDuplicateInReturn`, `LiftSuspicionInReturn`), each contact changed in their own unit (0101). Each gesture changes nothing, and records nothing, when it is so already. Suspected or lifted, a contact keeps their moment of modification. Forgotten, a contact lifts their suspicions, the others lifted in return.
- **Who**: who a contact is suspected of being a duplicate of is read by a member with the right to manage personal data only (`PersonReading::showsPossibleDuplicatesTo()`), the other members reading none, though they read the name of a contact (0129): the ticket says so, and the gesture is not theirs. The same member says, once checked, that a contact is no duplicate (`DismissPossibleDuplicate`, the intent `DismissPossibleDuplicate` of a contact only, `PersonalDataManagersOnly` without the right, `NotASuspectedDuplicateContact`, 409, for a contact not suspected of it, a refusal of its own so that its words name a contact): both keep the other among the contacts they are no duplicate of, the other by a reaction (`DismissSuspicionInReturn`), and are never suspected of it again; the dismissal modifies both.
- **In DynamoDB**, the link `RESEMBLANCE#<account>#<key>` / `PERSON#<id>` in the table of PersonalData finds the contacts of an account sharing a key, the key kept on the item of the person as `resemblanceKey`, written and deleted in the transaction of the person, written again by `app:keys:rebuild`.
- **On the screen**, the card of a contact flags their possible duplicates, a link to each, and « Ce n'est pas un doublon »; the endpoint `DELETE /api/contacts/{id}/possible-duplicates/{duplicateId}` waits for its MCP tool (0163), among the writes a later ticket covers.

## Consequences

- Until the reaction ran, only one of the two is flagged; `NotASuspectedDuplicateContact` concerns the contact of the gesture only.
- A contact added later to an organization where one resembles them, or removed from the one they shared, is not reconsidered until renamed: the suspicion follows the names, not the attachments.
- A contact collected a moment after another, before Prospecting attached the first one to its organizations, misses them: a suspicion has no safety net, it does not refuse.
- Two contacts stored before the key, resembling each other, are not suspected until one of them is renamed; a new one resembling one of them is, once `app:keys:rebuild` wrote their links.
- A first name and a last name typed in each other's field, a first name abbreviated, are not alike.
- The identity of a forgotten contact stays among those another is no duplicate of: an identity, no personal data, never given again.

## Alternatives considered

None beyond the choices the maintainer took on the ticket (the key, in the same organization only) and those of 0162, whose mechanism this one follows.

## Revisit when

Members dismiss many suspicions between people only sharing a name, or miss duplicates noted in another organization of the same company.
