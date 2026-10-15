# 0021. Use cases named after business intents

- Date: 2026-10-14
- Status: Proposed
- Implemented by: not yet; prepared by 0007 (URLs independent of use case names)

## Context

The use cases are CRUD-shaped (`createOrganization`, `updateOrganization`, `updateMyAccountInformation`): they say what changes in storage, not what the user means to do.

## Decision

The user plans to rename them after business intents. URLs stay resource-oriented (0007), so that only controllers follow.

## Consequences

- To decide when implementing: the list of intents.

## Alternatives considered

None were weighed yet: the intents are still to be found with the business.

## Revisit when

Before implementing it, together with 0020.
