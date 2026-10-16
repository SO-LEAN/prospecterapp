# 0050. The text search is a projection of word beginnings, kept by the events, rebuildable

- Date: 2026-10-16
- Status: Accepted
- Implemented by: `packages/core/src/SharedKernel/Domain/SearchTerms.php`, `Organization::searchTerms()`, `Person::searchTerms()`, `apps/api/src/Shared/Persistence/SearchProjection.php`, `apps/api/src/*/Persistence/*SearchIndex.php`, `bin/console app:search:rebuild`

## Context

The organizations were searched by corporate name or email, the contacts by name or email, with `LIKE '%text%'`. DynamoDB (0048) has no text search.

## Decision

- The search is a business rule of the core: `SearchTerms` normalizes a text (lower case, no accents, split on anything but letters and digits) and finds it when each searched word begins one of its words; an aggregate says what it is found by (`Organization::searchTerms()`: corporate name and email; `Person::searchTerms()`: names and email). The doubles of the scenarios apply it.
- The API answers it with a projection in the table of the context: one item per beginning of each word of a subject (`TOKEN#<scope>#<beginning>` / `<subject>`) within its scope (an account, an account and a purpose), and the list of what was indexed for a subject. A search looks up each word and keeps the subjects found by all, newest first, after the cursor (0049).
- The projection follows the domain events (`OrganizationSearchIndex`, `PersonSearchIndex` on the event bus): whatever happened to a subject, it is indexed again, or forgotten when gone. The aggregates remain the source of truth: `app:search:rebuild` clears the projection and indexes every aggregate again, as many times as needed, when an error left it drifting.

## Consequences

- "acm" finds "ACME", "gen elec" finds "Société Générale d'Électricité"; "cme" finds nothing: a word is found by its beginning, not its middle.
- One item per beginning of word: a few dozen per organization, written in batches.
- An error between saving an aggregate and indexing it leaves the projection behind until it is indexed again or rebuilt.

## Alternatives considered

- Reading every item of the account and filtering with `contains()`: exact substrings, but the cost grows with the account.
- Whole words only: fewer items, but the full word must be typed.
