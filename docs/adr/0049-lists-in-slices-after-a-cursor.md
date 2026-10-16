# 0049. Lists come in slices after a cursor, never in numbered pages

- Date: 2026-10-16
- Status: Accepted
- Implemented by: `packages/core/src/SharedKernel/Domain/Slice.php`, the search use cases (`after`, `max`, `next`), the links `next` of the lists of the API, `apps/front/src/shared/api/slices.ts`

## Context

The lists of organizations and contacts showed numbered pages and totals. DynamoDB (0048) counts nothing cheaply and reads a partition in key order from where the previous read stopped.

## Decision

- A list is a `Slice`: the items, newest first (the UUID v7 descending), and the cursor of the next slice, absent on the last. The repositories slice after a cursor, the use cases take `after` and `max` and answer `next`.
- The API gives the next slice as the link `next` of the list (HAL), the cursor in its query parameter `after`; no page number, no total.
- The front shows a slice after the other with "Charger plus" (infinite queries on the typed client); the dashboard counts the organizations it places on its map (at most 1000).

## Consequences

- No "page N of M" any more, and no total of the organizations beyond what a list holds.
- A cursor is the identity of the last item: a list stays stable while items are added at its top.

## Alternatives considered

- Counters kept per account to show totals and pages: more writes and code for a number nobody needed.
