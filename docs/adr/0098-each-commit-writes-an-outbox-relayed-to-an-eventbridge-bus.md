# 0098. Each commit writes its events in an outbox, relayed to a bus of EventBridge

- Date: 2026-11-10
- Type: architecture
- Status: Accepted
- Implemented by: `apps/api/src/Shared/Messaging/{CommitOutbox,OutboxDelivery,OutboxRelay,IntegrationEventBus}.php`, `apps/api/src/Shared/Persistence/{DynamoUnitOfWork,OutboxItem,OutboxItems,Tables}.php`, `apps/api/src/Shared/Command/RepublishOutboxCommand.php`, `apps/api/infra/{tables,messaging}.tf`, `docker/lambda/deploy.sh`

## Context

The reactions are to leave the request (ADR 0095, 0097) without losing an event: a command whose state is written must have its events published, and a command refused must publish none. Publishing on the bus within the request cannot be part of the transaction of DynamoDB: the state could be written without its events, or the events published for a state never written.

## Decision

- The delivery `outbox` (`INTEGRATION_EVENTS_DELIVERY=outbox`): the events of the open unit of work wait in `CommitOutbox`, the translators still running on `event.bus` before the commit; the outermost commit adds to its `TransactWriteItems` one item, in the table of the context of the use case: PK `OUTBOX#<commit>`, SK `OUTBOX`, the events as they travel in `events` (a JSON string), `occurredAt`, `expiresAt` a week later, under `attribute_not_exists(PK)`. No event, no item; over the 400 KB of an item, the commit is refused before anything is sent, a transaction never being split.
- A transaction spans one use case, ideally one aggregate, so a commit publishes in one context: one publishing in two is refused before anything is sent. What another context must do follows from the events, in its own transaction, consistent eventually. The gestures of the console grouping commands of Accounts and PersonalData in one unit (`CommandBus::dispatchTogether()`, ADR 0095) cannot go through the outbox until they are split.
- Every table streams its new items (`NEW_IMAGE`) and forgets an item past its `expiresAt` (TTL).
- The relay, a function of the package of the API (`BREF_RUNTIME=function`, `OutboxRelay`), is called by a mapping on the stream of each table, filtered on the insertions of `OUTBOX#`, ten records at a time from `TRIM_HORIZON`; it publishes their events on the bus `prospecter-<instance>-api-integration` by `PutEvents` of ten entries, and writes nothing. A put refusing an entry fails the whole batch, which the mapping splits and tries again five times, a record waiting a day at most, then sends its metadata to the queue `relay-failures` (14 days). An event may thus be published twice: the reactions are to be idempotent.
- `app:outbox:republish --since=<moment>` publishes again the items the tables still keep.
- Available, not in service: the delivery stays `sync` locally, in the tests and on the instances, until the subscriptions and their worker exist.

## Consequences

- The state of a command and its events are written all at once or not at all; the publication follows within a second, never before the commit.
- One more write per command, counted in the 100 of a transaction; a stream and a TTL on every table.
- MiniStack neither splits a failing batch nor stops retrying it: locally the age of a record is limited to a minute (`deploy.sh`), and the bisection and the five retries are first tried on AWS. `make db` recreates the tables, hence their streams: it deploys the relay again.

## Alternatives considered

None: the ticket set the outbox, its item and the relay, after a spike of MiniStack.

## Revisit when

The delivery `outbox` is put in service, or a command publishes more than an item can hold.
