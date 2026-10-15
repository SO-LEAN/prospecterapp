# 0019. An organization is reachable only by the organization prospecting it

- Date: 2026-10-15
- Status: Accepted
- Implemented by: ec09c80; `packages/core/src/Service/ProspectedOrganizations.php`

## Context

Writing the scenarios (0017) showed that any prospector could read or replace any organization by its id, and choose another organization's prospect as holding; only the logo removal checked ownership, with a 422.

## Decision

- A user reads its own organization and the organizations it prospects (owned by it); it replaces, removes the logo of, or picks as holding only the organizations it prospects.
- Any other organization is reported as not found (404), exactly like an unknown id, so that its existence does not leak.
- The rule lives in one collaborator of the hexagon, `ProspectedOrganizations`, used by the use cases.

## Consequences

- Organizations are partitioned by prospecting organization.
- A forbidden access and an unknown id cannot be told apart, also in logs and errors.

## Alternatives considered

- 403 for an existing organization of someone else: clearer for debugging, but reveals that the id exists.
- Checking ownership in the API controllers: the rule would not be part of the business and other deliveries would skip it.

## Revisit when

Organizations are shared between prospecting organizations (partners, groups), or an administrator needs a cross-organization view.
