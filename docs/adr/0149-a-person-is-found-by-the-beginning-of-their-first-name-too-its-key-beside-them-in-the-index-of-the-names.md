# 0149. A person is found by the beginning of their first name too, its key on an item beside them in the index of the names

- Date: 2026-11-18
- Type: architecture
- Status: Accepted
- Supersedes: for the person found by their first name, [0071](0071-search-by-the-beginning-of-the-name-or-of-the-email.md)
- Implemented by: `packages/core/src/SharedKernel/Domain/SearchKeys.php`, `Person::searchKeys()`, `features/rechercher-mes-contacts.feature`, `apps/api/src/PersonalData/Persistence/{DynamoPersonRepository,PersonalDataKeys}.php`, `apps/api/src/Shared/Persistence/SearchIndexes.php`

## Context

A person was found by the beginning of their last name or of their email (0071), never of their first name: a contact came out by their first name only when their email began with it, and a contact created without an email did not. The members search their contacts by their first name. An item has one key per index: the key of the last name already fills `NAME`.

## Decision

- A person is found by the beginning of their first name too (`SearchKeys::$firstName`), with or without the right to manage personal data (0129: the name is read in clear); the first name followed by the last name still finds no one.
- The key of the first name is carried by an item beside the person, `PERSON#<id>` / `FIRST_NAME`, of the same partition key, holding only the keys of the index `NAME`: the search reads the keys beginning with it in `NAME` as before and finds the person once, whichever of their names begins with it.
- The item is written and removed in the transaction of the person, absent when they have no first name; `app:keys:rebuild` writes it for the persons stored before.

## Consequences

- No new index on the tables, nothing to apply on the instances before the deployment; `app:keys:rebuild` once on each instance, for the contacts stored before to be found by their first name.
- One more write per save and per removal of a person having a first name: forgetting the contacts of an organization in one transaction reaches the 100 writes at about 33 contacts with an email, instead of about 50.

## Alternatives considered

- A fourth index, `FIRST_NAME`, on every table (`Tables`, `infra/tables.tf`): no item more nor write more in the transactions, but an index to create on each table of each instance, applied before the code reading it is deployed.

## Revisit when

- A forgetting of the contacts of an organization reaches the limit of a transaction, or the search must find a word in the middle of a name (0071).
