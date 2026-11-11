# 0101. The reactions run after the command, in a worker, each in its own transaction

- Date: 2026-11-11
- Type: architecture
- Status: Accepted
- Supersedes: for the reactions joining the unit of work of the command that published, [0095](0095-a-command-writes-its-aggregate-and-its-outbox-all-at-once.md); for the delivery of the reactions within the command, [0029](0029-cqrs-with-a-synchronous-messenger-command-bus.md), [0042](0042-contacts-never-alone-domain-events.md), [0043](0043-a-domain-event-per-intention-and-an-activity-journal.md), [0046](0046-integration-events-between-contexts.md)
- Implemented by: `apps/api/src/Shared/Messaging/{IntegrationEventConsumer,Invocation,Subscriptions,Subscription,IntegrationFailures,FailedInvocation,IntegrationWorker,EventLoop}.php`, `apps/api/src/Shared/Command/RedriveIntegrationFailuresCommand.php`, `apps/api/infra/messaging.tf`, `docker/lambda/deploy.sh`, `packages/core/tests/Double/SynchronousEvents.php`, `packages/core/features/une-commande-ecrit-tout-ou-rien.feature`, the reactions of Prospecting removing the files (`DiscardRemovedLogo`, `DiscardRemovedAttachment`, `DiscardAttachmentsOfErasedExchange`)

## Context

The commits write their events in an outbox relayed to a bus (ADR 0098), the reactions are the subscriptions of one file (ADR 0097), but they still ran within the command: a reaction refusing or failing refused the gesture of a member who had done nothing wrong, a gesture with many reactions (stopping the prospecting of an organization of some thirty exchanges) went over the 100 writes of a transaction, and a file removed from the storage during a command stayed removed when its unit was discarded afterwards. Making the contexts independent, so that one may leave for a service of its own, needs the reactions out of the command.

## Decision

- Every reaction, internal to a context as between contexts, runs after the command: a command succeeds as soon as its transaction is written; its events reach the reactions through its outbox, the relay and the bus. Only the translators (`Publish…Handler`) stay on `event.bus`, within the command.
- A rule of the bus per subscription invokes asynchronously the worker, a function of the package of the API (`BREF_RUNTIME=function`, `IntegrationEventConsumer`), with `{"subscription", "source", "detailType", "detail"}` (its input transformer). An invocation opens a unit of work, keeps the inbox of the subscription when it has one (ADR 0102), calls its reaction through its decorators, the transactional one joining the unit, and commits: the inbox, what the reaction writes and the outbox of what it publishes, one level deeper, the same correlation, the event it reacted to as its cause. Deeper than 5, an event is refused.
- A failure is sorted. Deterministic, another attempt repeating it (a `UseCaseRefused`, a refusal detected at the commit, an event the codec cannot read, an unknown subscription, a loop): the worker puts the invocation in `integration-failures`, in the envelope of the failures of Lambda (`requestContext.condition` `Refused`), and ends normally. Any other is thrown: Lambda retries twice (about one then two minutes later), an event waiting six hours at most, then puts it in the same queue (14 days, no consumer); a rule unable to invoke the worker retries for a day, then the same queue. In doubt, a failure is retried. A reaction with nothing left to do ends without throwing. `app:integration:redrive` gives the worker again the `requestPayload` of each message.
- The order is no promise: each reaction relies on its inbox, a revision or the state it reads again; one missing an earlier fact throws, and is retried.
- A file removed from the storage leaves it in a reaction to the event naming it (`LogoRemoved`, `AttachmentRemoved`, `ExchangeErased`), once the state is written.
- The scenarios of the core deliver the same way: the translators within the command, the reactions after it, each in its own unit, a failure kept instead of refusing the gesture.
- The delivery `outbox` is the one of the local stack; the tests and the instances keep `sync` until the reactions are put in service: the worker, its queue and the rules are deployed, and nothing reaches the bus.

## Consequences

- A gesture is no longer refused by a reaction, nor bounded by the writes of its reactions; what follows from it is eventually consistent, about a second later. The front reads again what a reaction changes, at once and while the reactions run (`readAgainAfterTheReactions`).
- A reaction refused or failing leaves the gesture done and its effect missing until the event is redriven: the queue of the failures has to be watched.
- MiniStack invokes the target of a rule without the retries of Lambda nor its destination (spike of MiniStack 1.5.27): locally a failure coming from a rule is only logged; the path of the failures is tested by invoking the worker asynchronously, and on an instance on AWS. Its first call takes some 16 seconds: `make e2e` warms the relay and the worker.
- Until the reactions are put in service, the sync delivery of the instances still runs them within the command, and refuses a gesture whose reaction refuses.

## Alternatives considered

None: the ticket set the worker, its failures and their sorting, after a spike of MiniStack. The front reading again what the reactions change, rather than the journeys reloading their pages, was Michel's choice.

## Revisit when

The reactions are put in service on the instances, or a reaction needs to run before the answer of its command.
