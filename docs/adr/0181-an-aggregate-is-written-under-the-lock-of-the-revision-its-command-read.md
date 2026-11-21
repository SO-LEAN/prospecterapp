# 0181. An aggregate is written under the lock of the revision its command read

- Date: 2026-11-21
- Type: architecture
- Status: Accepted
- Supersedes: for "no optimistic lock" on the revision of an aggregate, [0099](0099-the-contexts-publish-their-facts-by-event-and-by-sliced-service.md)
- Implemented by: `apps/api/src/Shared/Persistence/DynamoUnitOfWork.php`, `Table.php`, `RevisionLock.php`, `apps/api/src/Shared/CommandBus.php`, `DynamoOrganizationRepository`, `DynamoPersonRepository`, `DynamoExchangeRepository`, `packages/core/src/SharedKernel/Application/Addition.php`, `packages/core/src/SharedKernel/Domain/Exception/ChangedMeanwhile.php`

## Context
Two contacts collected at the same second for one organization, by two calls of an AI, each attached their contact
to the organization read before the other was attached: the last save won, the organization named one contact. The
other one missed the tags of the organization, and survived its erasure, with its e-mail. The item of an organization,
of a person and of an exchange carried its revision (ADR 0099), read by nothing when it was written.

## Decision
- The unit of work remembers each item it first reads by key, with its revision or its absence, until its outermost
  level ends. The save and the removal of an organization, a person and an exchange write its item under the
  condition that it is still as read: the same revision (none, for an item written before the revisions), or still
  absent. Outside a unit (the console, the demonstration), nothing is read before, no condition.
- The condition carries no refusal: the transaction cancelled throws `ConditionFailed`, as a conflict with another
  transaction does.
- A reaction is applied again from its reads, as before (ADR 0104).
- A command marked by the core as an addition (`Addition`: it only adds, contradicting nothing its client read, a
  contact collected or attached) is run again from its reads, three times at most after a random wait under 100 ms.
  Any other command, and an addition still cancelled, is refused: `ChangedMeanwhile`, a 409 `changedMeanwhile`; the
  member or the AI reads the record again and starts over.
- The revision compared is the one the command read, not the one the screen or the AI read before sending it.

## Consequences
- Two writes of one record at once lose neither: the addition is replayed, the change refused to its author.
- A screen or an AI saving a record read before another member changed it still overwrites that change without
  knowing it: the lock spans the command only, from its read to its commit.
- A 409 `changedMeanwhile` may now answer any command writing these aggregates; the front words it as any refusal.

## Alternatives considered
- The conflict replayed by the server for every command, or refused for every command: the maintainer chose to
  replay an addition and refuse a change (backlog #237).
- The revision the client read, exposed by the resources and the MCP answers and sent back with a change: weighed by
  the maintainer, left to a ticket of its own; it changes the contract, the forms of the SPA and the inputs of the MCP
  tools.

## Revisit when
The revision the client read is sent back with a change, or another aggregate is written by commands at once.
