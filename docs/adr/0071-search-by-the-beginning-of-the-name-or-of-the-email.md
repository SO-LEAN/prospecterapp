# 0071. An aggregate is found by the beginning of its name or of its email, read in indexes of its table

- Date: 2026-10-18
- Status: Accepted
- Supersedes: [0050](0050-text-search-by-a-projection-of-word-beginnings.md)
- Implemented by: `packages/core/src/SharedKernel/Domain/{SearchKey,SearchKeys,Email}.php`, `Organization::searchKeys()`, `Person::searchKeys()`, `apps/api/src/Shared/Persistence/{SearchIndexes,Table,Tables}.php`, `apps/api/infra/tables.tf`, `bin/console app:search:rebuild`

## Context

The projection of 0050 wrote about twenty items per aggregate (one per beginning of each word), plus the list of what it indexed, through two event handlers and a command to rebuild it: heavy to read in a table and to keep, for a search of a few names.

## Decision

- A search compares keys: a text in lower case, without accents, keeping only its letters and digits (`SearchKey`: "De la FO" is `delafo`, which begins `delafontaine`). An aggregate is found when its name key or its email key begins with the key searched (`SearchKeys`): the last name of a person, the corporate name of an organization. The email is kept in lower case (`Email`), so that an address is one whatever its case, for the search and for its uniqueness.
- The item of the aggregate carries its keys as the keys of two indexes of its table, `NAME` and `EMAIL`, partitioned by the scope of the search (an account, an account and a purpose), projecting only the keys; an aggregate without a name or an email is absent from that index. The keys are written with the aggregate, in its transaction: nothing to keep up to date apart.
- A search reads the keys beginning with it in both indexes, then the aggregates found, newest first after the cursor (0049).
- `app:search:rebuild` writes the keys of every aggregate again and removes the items of 0050, for the tables written before.

## Consequences

- "mai" finds "Maier", "jean@f" finds `jean@fables.test`; "fontaine" does not find "de La Fontaine", nor a first name a person: only the beginning of the name or of the email.
- No more search items, event handlers nor drift; two indexes per table instead, billed at each write of an aggregate.
- A search reads every key beginning with it before slicing: the cost grows with what a short search finds in an account.

## Alternatives considered

- One item per word, read by `begins_with`: every word of a name found, but items to keep apart again.
- The first and the last name together, in both orders (`michelmaier`, `maiermichel`): a name found with its first name typed whole, judged not worth it for now.

## Revisit when

- A search must find a word in the middle of a name, tolerate typing errors or rank what it finds: a search engine.
