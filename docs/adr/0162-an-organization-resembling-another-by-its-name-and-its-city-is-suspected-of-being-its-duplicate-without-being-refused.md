# 0162. An organization resembling another by its name and its city is suspected of being its duplicate, without being refused

- Date: 2026-11-20
- Type: domain
- Status: Accepted
- Implemented by: `packages/core/src/Prospecting/Domain/Model/{ResemblanceKey,Organization}.php`, `packages/core/src/Prospecting/Domain/Service/DuplicateSuspicion.php`, `packages/core/src/Prospecting/Application/{StartProspecting,ReviseLegalIdentity,Relocate,DismissPossibleDuplicate,SuspectDuplicateInReturn,LiftSuspicionInReturn,DismissSuspicionInReturn}`, `packages/core/features/{soupconner-un-doublon,dire-que-ce-n-est-pas-un-doublon}.feature`, `apps/api/src/Prospecting/Persistence/{DynamoOrganizationRepository,PossibleDuplicates}.php`, `apps/api/src/Prospecting/Controller/DismissPossibleDuplicateController.php`, `apps/front/src/features/organizations/components/{possible-duplicates,organization-card}.tsx`

## Context

The SIRET (0158), the LinkedIn page and the website (0160) refuse the true duplicates, but none is required: an organization entered twice by a member, or by an AI working for them, under a name written otherwise (« Acme SAS » and « ACME s.a.s », at Lyon) is not found. A resemblance is no proof: two companies may share a name in a city, so it cannot refuse.

## Decision

- **The resemblance key** (`ResemblanceKey`, of the domain): the corporate name, lower case, without accents, each word without what is no letter nor digit (`s.a.s` is `sas`), the words that are a legal form of a French company removed (SA, SAS, SASU, SARL, SARLU, EURL, SNC, SCI, SCOP, SCA, SCS, SCP, SCM, SELARL, SELAS, SELASU, SELAFA, GIE, EI, EIRL, SEM, SPL), the rest joined; then the city of its address normalized alike. No key without a name beside its legal form (an organization named by its email) or without a city: no suspicion.
- **The city typed, not the code of its municipality**: the code depends on the geocoder finding the address (one found and one not would never resemble), and Lyon, Paris and Marseille give each arrondissement a code of its own.
- **The suspicion is kept on the organizations, both ways**: started, renamed or moved, an organization is suspected of being a duplicate of each organization of its account sharing its key (`DuplicateSuspicion`, a domain service read before `save()`), and no longer of those it no longer resembles; it records `DuplicateSuspected` or `DuplicateSuspicionLifted` on itself only, and a reaction suspects or lifts the other one in return, each organization changed in its own unit (0042, 0101). Each gesture changes nothing, and records nothing, when it is so already: the reaction to the reaction ends there.
- **Dismissed for good**: a member says, once checked, that it is no duplicate (`DismissPossibleDuplicate`, every prospector of the account, refused for an organization it is not suspected of, `NotASuspectedDuplicate`); both keep the other among the organizations they are no duplicate of, the other by a reaction, and are never suspected of it again, whatever their names and cities become.
- **No modification**: suspected or lifted, an organization keeps its moment of modification (as 0142); the dismissal, a gesture of a member, modifies both.
- **The reading drops the organizations no longer prospected**: the sheet and the list name the possible duplicates still prospected, read in one batch; stopping to prospect one lifts nothing on the other.
- **In DynamoDB**, the link `RESEMBLANCE#<account>#<key>` / `ORGANIZATION#<id>` finds the organizations of an account sharing a key, written and deleted in the transaction of the organization, written again by `app:keys:rebuild`.

## Consequences

- Until the reaction ran, only one of the two is flagged; `NotASuspectedDuplicate` concerns the organization of the gesture only.
- Two organizations stored before the key, resembling each other, are not suspected until one of them is started, renamed or moved again; a new one resembling one of them is, once `app:keys:rebuild` wrote their links.
- Two duplicates entered at the same instant may miss each other: a suspicion has no safety net, it does not refuse.
- « Lyon » and « Lyon 3e », a name written with its words split otherwise (« S. A. S. »), are not alike.
- The identity of an organization no longer prospected stays in the documents of those it was suspected or dismissed of, never given again; nothing reads it.
- The mechanism (a key of the domain, a link by account, a suspicion kept both ways by reactions, a dismissal kept for good) is the one a suspicion between other aggregates follows, the contacts of an organization first.

## Alternatives considered

The suspicion computed at each reading, from the link of the key, was weighed while planning: a list would read the link of each of its organizations; kept on the organization, a page reads it from its items.

## Revisit when

Members dismiss many suspicions between companies only sharing a name, or miss duplicates the key does not see (another city written, a name abbreviated).
