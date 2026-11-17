# 0141. The tags of a contact are derived from the facts Campaigns keeps of its organizations and of its reach, rebuilt without versions

- Date: 2026-11-17
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/src/Campaigns`, `packages/core/features/porter-les-tags-de-mes-organisations-et-de-mes-coordonnees.feature`, `apps/api/src/Campaigns`, `apps/api/src/Tags`, `apps/front/src/features/tags`

## Context

A contact must carry the tags put by hand on its organizations, following their additions, removals and the contacts attached or detached, and the automatic tags of how it is reached: #EMAIL with an e-mail, #PHONE with a phone, #SMS when that phone is a mobile, #LINKEDIN with a LinkedIn profile. ADR 0138 announced them as a projection of Campaigns beside the tagging, rebuilt from the other contexts. Campaigns knows neither the contacts of an organization (Prospecting) nor the contact details of a person (PersonalData, personal data), and Reporting rebuilds its figures in versions, waiting two minutes for the recordings in flight (ADR 0106), because its figures are sums.

## Decision

- **The projection is the facts Campaigns keeps**, per account: the last fact of each organization (its revision, whether it is prospected, the contacts it lists) and of each contact (its revision, whether it is kept, how it is reached: four booleans, no personal data). A fact replaces the known one as in Reporting (ADR 0099): the terminal fact ends the aggregate, otherwise the highest revision wins.
- **The tags of a contact are derived at the reading**, by a rule of the domain (`ContactTags`): its own (its tagging), those put by hand on each organization whose fact lists it, naming every organization they come from, and the automatic ones of its reach (`Reach`); a tag carried by hand and inherited too is its own, shown once. No list of tags is kept per contact: a change of the tags of an organization needs no reaction.
- **The facts published grow a version**: `OrganizationFactsChanged.v2` lists the contacts of the organization, `ContactFactsChanged.v2` says how the contact is reached (`hasEmail`, `hasPhone`, `hasMobile` as libphonenumber types the number, `hasLinkedIn`), and so do the services of facts. Reporting and the forgetting of the tags of an organization listen to both versions; the recording of Campaigns to the second only, `app:tags:rebuild` catching a fact published in the first.
- **The rebuild records the facts the services give again, through the same succession, without version nor wait**: the facts replace each other by revision and are never added, so a recording of the worker and one of the rebuild commute, each written under the condition of the fact it replaces. A fact left behind by a terminal fact missed is read by no one (its organization lost its tagging with it, its contact is forgotten); the check of the consistency reports it.
- **A contact's tags are read by its keys**: recording the fact of an organization writes a link per contact it lists and removes those of the contacts it no longer lists, in its transaction; the tags of a contact are read from its fact and its links, then the taggings by key, never the whole partition of the account.
- One intent per holder: the tags of an organization (`ConsultOrganizationTags`), those of contacts, a set of them (`ConsultContactTags`), for the card, the answers of tagging a contact and the list of the contacts.

## Consequences

- Inheriting costs nothing at the tagging of an organization, and the rebuild runs at once, for one account or all of them.
- An organization fact writes a link per contact added or removed: an organization listing close to a hundred contacts at once would exceed a transaction of DynamoDB.
- Until `app:tags:rebuild` runs on an instance, its contacts carry no inherited nor automatic tag; a fact published in the first version after the deployment is caught only by it.
- The search of the contacts by tags will read the tags of each contact, or keep beside them the contacts of each tag.

## Alternatives considered

- Reading the whole partition of the account at each reading of a contact, without links (weighed while planning): simpler, but a reading growing with the account, where the partition is read whole only to propose its tags.
- Versions and waits as in Reporting (ADR 0106), as the ticket suggested: needed for sums, not for facts replacing each other.

## Revisit when

The search by tags needs the contacts of a tag without reading each contact, or an organization lists so many contacts that its links no longer fit in a transaction.
