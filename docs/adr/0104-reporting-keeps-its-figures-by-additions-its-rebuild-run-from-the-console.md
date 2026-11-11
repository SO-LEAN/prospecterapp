# 0104. Reporting keeps its figures by additions in a table of its own, its rebuild run from the console

- Date: 2026-11-11
- Type: architecture
- Status: Accepted
- Supersedes: for « several domain events of one command publish the same snapshot several times » and « one more integration event per domain event », [0099](0099-the-contexts-publish-their-facts-by-event-and-by-sliced-service.md); for a conflict thrown at once for Lambda to retry, [0101](0101-the-reactions-run-after-the-command-in-a-worker.md)
- Implemented by: `packages/core/src/Reporting/Domain/Repository/FactRepository.php`, `apps/api/src/Reporting/{Persistence,AntiCorruption,Command}`, `apps/api/src/Reporting/RecordingsOfTheWorker.php`, `apps/api/src/Shared/Persistence/{Write,Table,DynamoUnitOfWork}.php`, `apps/api/src/Shared/Messaging/{Subscriptions,IntegrationEventConsumer}.php`, `apps/api/src/Shared/DependencyInjection/SubscriptionsPass.php`, `apps/api/config/subscriptions.json`, `apps/api/infra/tables.tf`

## Context

The Reporting context of the core (ADR 0103) keeps the figures of each account from the facts the other contexts publish (ADR 0099), and counts them again from their state. The API has to give it a table, deliver it the facts, and run its rebuild. Facts of one account arrive at the same time, in several invocations of the worker or, with the delivery `sync`, several in the unit of one command; a fact may change between the moment it is read and the moment the one replacing it is written.

## Decision

- A table of its own, `<prefix>-reporting`, of the shape of the others (ADR 0038, 0048): the versions under `PROJECTION`, the facts of an account under `FACTS#<version>#<account>`, its figures under `FIGURES#<version>#<account>` (`TOTAL`, `MONTH#<YYYY-MM>`); no personal data, identities and booleans. A terminal fact is forgotten 15 days later, longer than an event may wait.
- The port `FactRepository::record()` of the core is given the fact the recorded one replaces, the one the change was computed from: a fact is written in its place under the condition that the version still keeps it, its revision and its presence (a terminal fact has the revision 0, like a fact written before the revisions), with an `ADD` of the non-zero differences on the figures, in one transaction: the counters are added to, never written, so that two facts of one account recorded at the same time both count.
- The unit of work combines two writes of one item instead of keeping the last: two additions are one, their sum; a conditional put replacing another keeps the condition of the first, about the item as stored. An addition is not seen by the reads of the unit.
- The outbox of a commit keeps an event published again (the same source, type and payload) once: the facts of an aggregate are published for each of its domain events (ADR 0099), and each one delivered is now a reaction of the worker.
- The worker applies again from its reads, three times at most after a short random wait, a transaction cancelled by a condition carrying no refusal or by a conflict with another transaction, then throws for Lambda to retry: for every subscription, the unit being all or nothing.
- One subscription, `reporting-figures`, names a handler per detail-type (`handlers`), without inbox: the revision makes a fact applied twice harmless.
- `app:reporting:rebuild` calls `RebuildFiguresHandler` out of the command bus (its steps commit their own units), waits a minute for the recordings of the worker (28 s), and removes the former version in batches written at once. A rebuild starts by removing every version but the active one: what an interrupted rebuild left, and what a recording wrote in the former version after its removal. A step cancelled by a recording of the worker on the same figures (the `TOTAL` of the account, written in both live versions) starts the whole rebuild again, three times at most: the core commits each step itself, and a retry per step would be a change of the core. It runs from the command line, on an instance with `make console INSTANCE=`, out of the 15 minutes of a function; `make db` runs it after the data of the demonstration.

## Consequences

- The figures are exact whatever the number of facts recorded at once, and a fact applied twice counts once.
- With the delivery `sync`, the reactions of Reporting join the unit of the command: once a version is active, each fact adds a write to the 100 of a transaction, the figures of an account being one write per unit.
- The two live versions of a rebuild are written in the one transaction of the reaction, not one each: the worker holds its reaction in one unit, and a conflict on either applies both again.
- `make db` waits a minute longer; the local stack warms eight containers of the worker before the journeys, a gesture having more reactions running at once.

## Alternatives considered

- A transaction per live version, as the ticket first described it: the reaction would have to write out of the unit the worker opens for it; one transaction for both, retried together, keeps the reaction all or nothing.
- The retry in the repository of the facts: it cannot read again the fact the core decided from; the worker applies the reaction again from its reads.

## Revisit when

A rebuild of every account takes longer than an operator waits, or a command publishes more facts than a transaction holds.
