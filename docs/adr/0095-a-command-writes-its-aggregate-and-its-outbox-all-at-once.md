# 0095. A command writes its aggregate and its outbox all at once, or nothing

- Date: 2026-11-09
- Type: architecture
- Status: Accepted
- Supersedes: for « No transaction », [0042](0042-contacts-never-alone-domain-events.md), which it states more precisely
- Implemented by: `packages/core/src/SharedKernel/Application/{Port/UnitOfWork,TransactionalCommandHandler,TransactionalEventHandler,RefusalTranslatingEventHandler}.php`, `apps/api/src/Shared/Persistence/{DynamoUnitOfWork,Write,Table}.php`, `apps/api/src/Shared/DependencyInjection/RefusalTranslatingHandlersPass.php`

## Context

`save()` wrote at once, then each reaction wrote on its own on the synchronous event bus. A reaction throwing afterwards left the state of the command written without what follows from it, stopped the delivery of the other events, and the client received an error for a command kept anyway; nothing was tried again. The outbox to come, the events kept with the state they follow from, needs the state and the events written together first.

## Decision

- The core owns a port, `UnitOfWork` (`begin()`, `commit()`, `discard()`): what a use case writes, its state and what follows from it, is kept all at once or not at all. A `begin()` within an open unit joins it, only the outermost `commit()` writes; a `discard()` after the `commit()` of its level does nothing, a `discard()` without it abandons the whole unit, whatever its level, until its outermost level ends.
- Three decorators of the core, built by the clients: `TransactionalCommandHandler` and `TransactionalEventHandler` begin the unit, commit it when the handler succeeds, discard it in a `finally`; `RefusalTranslatingEventHandler` translates the refusal of a reaction like that of a command. The order is fixed: the translation of the refusals outside, the unit of work inside, so that a refusal detected when the unit commits is translated too (409, not 500). The reactions stay synchronous: they join the unit of the command that published.
- This is the technical atomicity of a use case, not a business transaction between aggregates: a use case still changes the aggregate of its intent, the other aggregates and contexts follow from its events (ADR 0042), and every aggregate is still written by its own repository.
- In the API, `DynamoUnitOfWork` keeps the writes of the tables (`Table::transact()`), one per item (table, PK, SK), the last one winning, and sends them in one `TransactWriteItems` at the outermost commit; without an open unit (the data of the demonstration, the commands of the console that rebuild), a write is sent at once. More than 100 writes are refused with a `LogicException` before anything is sent: the transaction is never split. A conditional write carries the refusal to throw when its condition does not hold (`EmailAlreadyUsed`), read from the `CancellationReasons`.
- A read by key and the query of a partition see what the open unit is about to write (a delete reads as an absence), so that a reaction sees the state its command leaves; a count, the keys of the search and a scan read the table as it was.
- The activity journal is written with the use case; a gesture of the console made of several commands dispatches them together (`CommandBus::dispatchTogether()`).

## Consequences

- A command refused or failing, by itself, by a reaction, or at the commit, leaves the tables as they were, the journal included.
- A gesture writing more than 100 items is refused whole: stopping the prospecting of an organization with some thirty exchanges or more (about three writes for each, its journal included) fails until its reactions leave the transaction with the outbox.
- A unit discarded at one level stays abandoned until its outermost level ends: Messenger still calls the other handlers of an event after one of them threw, and what they write is thrown away too.
- What a use case does outside DynamoDB is not undone: a file sent to the storage before a unit is discarded stays there unused; a file removed by a use case whose unit is discarded afterwards is lost for an aggregate that still names it. Removing files belongs after the commit, in a reaction delivered once the state is written.
- The unit of work keeps the state of a request: the only service of the API that does, emptied whenever its outermost level ends.

## Alternatives considered

- The unit of work outside the translation of the refusals: refused, a refusal detected at the commit would come out untranslated, a 500 instead of a 409.
- Splitting a unit of more than 100 writes into several transactions: refused, it would give up the all or nothing the decision is for.

## Revisit when

The outbox delivers the events asynchronously: the reactions then leave the unit of the command, each in its own.
