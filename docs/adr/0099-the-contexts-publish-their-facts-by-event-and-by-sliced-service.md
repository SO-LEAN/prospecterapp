# 0099. The contexts publish their facts, by an event and by a sliced service

- Date: 2026-11-10
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/src/Prospecting/Contract/{Event/OrganizationFactsChanged,Event/ExchangeFactsChanged,Service/ListOrganizationFacts,Service/ListExchangeFacts}`, `packages/core/src/Prospecting/Application/{PublishOrganizationFacts,PublishExchangeFacts,ListOrganizationFacts,ListExchangeFacts,Port/OrganizationFactsRetriever,Port/ExchangeFactsRetriever}`, `apps/api/src/Prospecting/Persistence/{DynamoOrganizationFactsRetriever,DynamoExchangeFactsRetriever}.php`, `apps/api/src/Shared/Persistence/Revision.php`

## Context

A context counting what another one holds (Reporting, to come, counting the organizations and the exchanges of an account) must not read its tables (ADR 0038, 0045). The domain events never leave their context (ADR 0046), and an integration event per intention would make the counting context follow every intention of the other one. It needs the state of what changed, and a way to count everything again from the state, when it starts or when it went wrong.

## Decision

- A context publishes the **facts** of an aggregate each time it changes: an integration event carrying a snapshot of what the others count, not what happened (`OrganizationFactsChanged`, `ExchangeFactsChanged` of Prospecting: present, located, the moment of the exchange). A translator receives every domain event of the aggregate (`OrganizationEvent`, `ExchangeEvent`), reads the snapshot and publishes it, within the command, before the commit: the unit of work gives the state it is about to write (ADR 0095). Several domain events of one command publish the same snapshot several times, which is harmless.
- An aggregate no longer found (removed) gives a **terminal fact**: absent, at the revision 0.
- The snapshot carries the **revision** of the aggregate, written by its repository in its item at each save (`revision + 1`): a matter of persistence the aggregate does not know (ADR 0048), no optimistic lock. The highest revision is the latest; a counting context keeps the facts of the highest one, and the terminal fact ends it.
- The same snapshots are given by a **published service** (ADR 0089), a slice after a cursor (ADR 0049) of the aggregates of an account (`ListOrganizationFactsService`, `ListExchangeFactsService`), refusing nothing: an unknown account has no facts. One way to compute them: the event and the service read the same retriever (`OrganizationFactsRetriever`, `ExchangeFactsRetriever`).
- The exchanges are listed by account in `GSI2` of their item (`ACCOUNT_EXCHANGES#<account>`, their id); `app:search:rebuild` writes the key of the exchanges written before.
- An exchange erased because no organization keeps it any more is erased by its intention (`erase()`), so that its erasure is published like the one a member asks for.

## Consequences

- A counting context depends on two events and two services per context, not on its intentions; a new intention of Prospecting changes nothing for it.
- Each command of Prospecting publishes one more integration event per domain event, in the outbox of its commit (ADR 0098).
- An item written before the revision has none: it reads as the revision 0 until its next save.
- The facts of PersonalData and the list of the accounts follow the same pattern, in a ticket of their own.

## Alternatives considered

None: the ticket set the event of facts, the revision written by the repository and the sliced service.

## Revisit when

A counting context needs a fact the snapshots do not carry, or the revision must order writes made concurrently.
