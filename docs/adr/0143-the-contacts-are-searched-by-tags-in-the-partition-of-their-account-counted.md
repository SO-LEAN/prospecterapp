# 0143. The contacts are searched by tags in the partition of their account, read once, counted

- Date: 2026-11-17
- Type: architecture
- Status: Accepted
- Supersedes: for the contacts found by tags, the rule of [0049](0049-lists-in-slices-after-a-cursor.md) that a list is never counted
- Implemented by: `packages/core/src/Campaigns` (`SearchContactsByTags`, `TagSelection`, `ContactTags::carriesAll()`, `TaggedContactsRetriever`), `packages/core/features/rechercher-mes-contacts-par-tags.feature`, `apps/api/src/Campaigns/Persistence/DynamoTaggedContactsRetriever.php`, `apps/api/src/Contacts/Controller/SearchContactsByTagsController.php`, `apps/front/src/routes/_app/contacts/index.tsx`, `apps/front/src/features/tags`

## Context

A campaign starts from the contacts carrying every tag chosen (#LINKEDIN #STRASBOURG #ESN), their own, inherited from their organizations and automatic alike, and the member wants to know how many there are. The search must stay free on DynamoDB, without a search engine. The tags of a contact are derived at the reading from the facts Campaigns keeps, no list of tags kept per contact (ADR 0141), which named this search as the signal to revisit.

## Decision

- The search reads the partition of the account in the table of Campaigns once: a `Query` of the facts of the contacts and their links, a `Query` of the taggings. Campaigns derives the tags of each contact its fact keeps by the rule in force (`ContactTags`), keeps those carrying the 1 to 5 tags chosen (`TagSelection`), newest first, and answers a slice after a cursor with the number found in all. Nothing is written for it: no rebuild nor check to add.
- Every prospector searches by tags, with or without the right to manage personal data: tags are no personal data. The API composes, as for the list of the contacts: Campaigns finds the contacts, PersonalData reads them in clear or masked, then their organizations and their tags.
- The list of the contacts found by tags is counted, unlike the other lists.

## Consequences

- Some 400 bytes per contact (its fact, its links, its share of the facts and taggings of its organizations): a page of 1 MB holds some 2,500 contacts; an account of 10,000 contacts is read in four pages, some half a second and 500 read units, a fraction of a cent per thousand searches on demand. An account of tens of thousands of contacts makes the search slow.
- Inheriting still costs nothing at the tagging of an organization, and a tag derived later from another fact (the geographic tags of an organization) is searched without anything more.
- The count is exact, the whole partition being read anyway.

## Alternatives considered

- An item per contact and tag in a partition of the tag, a count per tag, the rarest tag read then the contacts carrying the others kept (proposed by the ticket): its cost bounded by the smallest set, but a tag of a contact is derived, so tagging an organization writes an item per contact it names (an organization of some 100 contacts no longer fits in a transaction), attaching or detaching a contact one per tag of the organization, a tag inherited from two organizations and carried by hand needs counting its sources, and the counts drift unless rebuilt; it reverses the derivation at the reading of ADR 0141, for volumes the reading of the partition covers.

## Revisit when

An account holds tens of thousands of contacts and the search slows, or a campaign needs a union or an exclusion of tags over large sets.
