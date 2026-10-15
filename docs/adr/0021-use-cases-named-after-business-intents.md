# 0021. Use cases named after business intents

- Date: 2026-10-14
- Status: Proposed
- Implemented by: not yet; prepared by 0007 (URLs independent of use case names)

## Context

The use cases are CRUD-shaped (`createOrganization`, `updateOrganization`, `updateMyAccountInformation`): they say what changes in storage, not what the user means to do.

## Decision

They will be renamed, and split where needed, after business intents (for instance "start prospecting an organization", "move an organization to another holding"). URLs stay resource-oriented, so only controllers follow; features (0017) are reorganized by intent.

## Consequences

- To decide when implementing: the list of intents, with the business. A full replacement `PUT` may become several commands (0020).

## Alternatives considered

- Keep CRUD names: simple, but the core says nothing about the business.

## Revisit when

Before implementing it, together with 0020.
