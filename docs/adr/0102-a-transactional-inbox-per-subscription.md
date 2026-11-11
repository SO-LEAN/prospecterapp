# 0102. A transactional inbox per subscription

- Date: 2026-11-11
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/src/Shared/Messaging/{Inbox,AlreadyApplied}.php`, `apps/api/src/Shared/Persistence/{Write,DynamoUnitOfWork}.php`, `apps/api/config/subscriptions.json`

## Context

The bus delivers at least once: the relay may publish an event twice (ADR 0098), EventBridge and Lambda retry, `app:outbox:republish` and `app:integration:redrive` give events again. A reaction applied twice would write twice what it writes once (a contact attached twice, a profile collected twice).

## Decision

- A subscription declares `"inbox": true` in `config/subscriptions.json` when applying its event twice would change something. The worker then writes, in the unit of work of the reaction, an item in the table of the context of the subscription: PK `INBOX#<subscription>`, SK the `eventId`, `receivedAt`, `expiresAt` 15 days later, under `attribute_not_exists(SK)`; its condition failing at the commit throws `AlreadyApplied`, the refusal its write carries, and the worker ends the invocation normally, nothing written.
- The inbox is written with what the reaction writes, all or nothing: a reaction that fails writes none, so the event is applied when it comes again; nothing is to be freed.
- Two subscriptions to the same event have their own inbox: one never replays nor hides the other.
- 15 days, longer than an event may wait: 6 hours in the worker, a day of retries of EventBridge, 14 days in the queue of the failures.
- A reaction removing a file is idempotent by itself (a file removed again is absent), without an inbox.

## Consequences

- One more write per reaction, counted in the 100 of its transaction; the duplicate is found at the commit, after the reaction ran, its writes thrown away.
- A conditional write now carries what to throw, a refusal of the domain or `AlreadyApplied`.

## Alternatives considered

None: the ticket set the inbox, its item and its retention.

## Revisit when

A reaction writes outside the tables of its context (a service of AWS, the storage), where the inbox cannot join its transaction.
