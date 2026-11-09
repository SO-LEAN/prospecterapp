# 0097. Events travel in the shape of EventBridge events, and the reactions are the subscriptions of one file

- Date: 2026-11-09
- Type: architecture
- Status: Accepted
- Supersedes: for the delivery of the reactions on the Messenger `event.bus`, [0042](0042-contacts-never-alone-domain-events.md); for « the only service of the API » keeping the state of a request, [0095](0095-a-command-writes-its-aggregate-and-its-outbox-all-at-once.md)
- Implemented by: `apps/api/src/Shared/Messaging/*`, `apps/api/src/Shared/DependencyInjection/SubscriptionsPass.php`, `apps/api/config/subscriptions.json`, `apps/api/config/packages/messenger.yaml`

## Context

The reactions are to leave the command for a bus (EventBridge, an outbox, a worker), each in its own transaction. For that, an event must know how to travel, the list of the reactions must be readable by the infrastructure as well as by Symfony, and reactions answering each other must not loop on a bus where nobody waits for them. The behaviour must not change before the bus exists.

## Decision

- Every event travels as an event of EventBridge: `source` `prospecter.<context>` for an integration event, `prospecter.<context>.internal` for a domain event; `detail-type` the short name of the class and the version of its contract (`ContactCollected.v1`); `detail` the envelope `eventId`, `occurredAt`, `correlationId`, `causationId`, `depth` and the `payload`, the event as plain data. The classes are listed explicitly (`DomainEventTypes`, `IntegrationEventTypes`), one codec (`EventCodec`) encodes both kinds; the PHP contract carries no identity, the envelope does.
- The reactions are the subscriptions of `apps/api/config/subscriptions.json`, named `<context>-<intent>`, one handler per type of event, read by Symfony (`SubscriptionsPass`, the parameter `app.subscriptions`) and later by Terraform. Only the translators (`Publish…Handler`) stay on `event.bus`; a reaction no subscription names fails the build.
- The port `Events` is `RoutingEvents`: a domain event to its translators on `event.bus`, then every event to a `Delivery`, named by `INTEGRATION_EVENTS_DELIVERY`; `sync`, the only one for now, encodes, carries through JSON, decodes and calls the reactions at once, within the unit of work of the command.
- No loop: a command gives a correlation (the middleware `CorrelatedCommands`), an event published by a reaction is one level deeper than the event it reacts to and names it as its cause, a translation keeps the depth of its domain event; beyond the depth 5 (`MAX_EVENT_DEPTH`) the delivery throws `EventLoop`.
- The cause of what is published is ambient: the port `Events` carries none and the reactions are called directly, so `Causation` keeps the causes of the request (`ResetInterface`), a second service keeping the state of a request after the unit of work.

## Consequences

- The bus, the outbox and the worker only add a `Delivery` and read the same file; the contracts and the reactions do not change.
- A new event class goes in its list, a new reaction in the file, or the tests fail (`EventCodecTest`, `ReactionsAreSubscribedTest`).
- Within a command the order of the reactions is no promise: a translator and the reactions to its integration event now run before the internal reactions of the same domain event (`CollectTheFounderProfile` before `RegisterTheFounder`). Nobody may rely on it: on a bus there is no order at all.
- The list of the domain events names those of every context, an exception to `BoundedContextsTest`; a domain event is still read only in its context, checked on the subscriptions.

## Alternatives considered

- `bref/symfony-messenger` and its `EventBridgeTransport` (weighed in the ticket): refused, it always publishes the `detail-type` `Symfony Messenger message`, which a rule of EventBridge cannot route by type.

## Revisit when

The asynchronous delivery is in service: the inbox of the subscriptions, the order, and how a failing reaction is tried again are then decided.
