# The bounded contexts

What is in force; the reasons are in the records it names (`docs/adr`), read only to know why or before changing it (ADR 0090).

Accounts, PersonalData and Prospecting (ADR 0030, 0031), Reporting downstream of them (ADR 0103), and the shared kernel of what they all share (`core.md`). Their language and their rules: `docs/domain/accounts.md`, `personal-data.md`, `prospecting.md`, `reporting.md`.

## What a context sees of another

- A context never uses another one: it declares a port in its own words (`Prospectors`, `Readers`, `KnownContacts`, `ProspectedOrganizations`), implemented outside the core by an anticorruption adapter (`App\Prospecting\AntiCorruption\AccountsProspectors`, `App\PersonalData\AntiCorruption\AccountsReaders`, `App\Prospecting\AntiCorruption\PersonalDataKnownContacts`, `App\PersonalData\AntiCorruption\ProspectingOrganizations`), doubled in the scenarios by the same code (`tests/Double`). The adapter sees of the other context only what it publishes (ADR 0045): the service its `Contract/Service` declares (ADR 0089: `ConsultMembersService`, `WhichAreContactsService`, `WhichAreProspectedService`), injected by its type, implemented by the handler of the query in its `Application`, its answer kept by `KeptMembers`, `KeptContacts`, `KeptProspected`. A published query asks about a set, a single element being a set of one, and refuses nothing: what is unknown is absent from its answer, the client deciding what an absence means; it answers plain data with only what the others need, checking no caller, fixed by `features/langage-publie.feature`; `tests/Architecture/BoundedContextsTest` fails otherwise. A port of the asking context asks about a set where a use case asks about several elements (`ProspectedOrganizations::prospectedAmong()`). A new question between contexts is a new published service, its scenario, and the rule it answers in the aggregate (`Person::isAContactOf()`).

## What each publishes

| Context | Services (`Contract/Service`, ADR 0089) | Integration events (`Contract/Event`, ADR 0046) |
|---|---|---|
| Accounts | `ConsultMembersService`: who the members are; `ListAccountsService`: the identities of the accounts, by slice | `AccountOpened`, `MemberRegistered` |
| PersonalData | `WhichAreContactsService`: which persons are contacts of an account; `ListContactFactsService`: the facts of the contacts of an account, by slice | `ContactCollected`, `ContactForgotten`, `ContactFactsChanged` |
| Prospecting | `WhichAreProspectedService`: which organizations an account prospects; `ListOrganizationFactsService`, `ListExchangeFactsService`: the facts of the organizations and of the exchanges of an account, by slice | `ContactsLeftWithoutOrganization`, `OrganizationFactsChanged`, `ExchangeFactsChanged` |
| Reporting | nothing | nothing |

A context counting what another one holds reads its **facts** (ADR 0099): a snapshot of an aggregate published each time it changes (`OrganizationFactsChanged`: present, located; `ExchangeFactsChanged`: present, the moment of the exchange; `ContactFactsChanged`: present), by a translator receiving every domain event of the aggregate (`PublishOrganizationFactsHandler` of `OrganizationEvent`, `PublishExchangeFactsHandler` of `ExchangeEvent`, `PublishContactFactsHandler` of `PersonEvent`, which publishes nothing for the profile of a member, no contact) before the commit, with the revision its repository writes (`persistence.md`), the highest the latest; an aggregate removed gives a terminal fact, absent at the revision 0. The service of the same facts gives them again by slice, to count everything from the state; both read one retriever (`OrganizationFactsRetriever`, `ExchangeFactsRetriever`, `ContactFactsRetriever`). `ListAccountsService` tells which accounts there are to count (`AccountListRetriever`). Reporting counts them (ADR 0103): its reactions record the facts received (`RecordOrganizationFacts`, `RecordExchangeFacts`, `RecordContactFacts`), and a rebuild reads them again through its ports `SourceAccounts`, `ProspectingFacts` and `PersonalDataFacts`, the anticorruption layers asking the services by slice (`App\Reporting\AntiCorruption\AccountsSourceAccounts`, `ProspectingFactsFromProspecting`, `PersonalDataFactsFromPersonalData`); in the API, the subscription `reporting-figures` delivers it the facts, and `app:reporting:rebuild` counts again (ADR 0104, `persistence.md`).

In the API, the anticorruption layers implementing the ports of a context are in `src/<Context>/AntiCorruption` (ADR 0086), the delivery of a context uses nothing of another but its Contract (`api.md`).

## How events travel

The whole mechanism, drawn, with its failures and its operations: `messaging.md`.

Every event leaves its context in the shape of an event of EventBridge (ADR 0097). With the delivery `outbox` (`INTEGRATION_EVENTS_DELIVERY`, the local stack and the instances, ADR 0108), it is written with the command and relayed to the bus of EventBridge (ADR 0098, `persistence.md`), whose rules give it to the worker, which runs the reactions after the command, each in its own transaction (ADR 0101, [below](#the-worker)). The tests keep the delivery `sync`, and so does an instance naming it in its value `integration_events_delivery` (`infra.md`): every event delivered at once, within the command, a reaction refusing refusing the gesture.

| Field | Value |
|---|---|
| `source` | `prospecter.<context>` (`prospecter.accounts`, `prospecter.personal-data`, `prospecter.prospecting`) for an integration event; `prospecter.<context>.internal` for a domain event |
| `detail-type` | the short name of the class followed by the version of its contract: `ContactCollected.v1` |
| `detail` | `{"eventId": "<uuid v7>", "occurredAt": "<ISO 8601 UTC, microseconds>", "correlationId": "<uuid v7>", "causationId": "<uuid v7 or null>", "depth": 0, "payload": {…}}` |

- The classes are listed explicitly, by source and detail-type, in `App\Shared\Messaging\IntegrationEventTypes` and `DomainEventTypes`; a domain event and an integration event of the same name (`AccountOpened`) are told apart by their source. `EventCodec` writes the payload by the parameters of the constructor: an identity as its string, a moment as the documents write it (`Shared\Persistence\Documents::moment()`), a list element by element, read back by the types of the parameters and their docblocks. The PHP contract carries no identity: the `eventId` belongs to the envelope. A new event class goes in its list, or `tests/Shared/Messaging/EventCodecTest` fails; each survives a round trip through JSON. A change of the payload of an event bumps its version once an outbox or a queue of failures may hold the former shape, as those of the instances may since they deliver `outbox` (ADR 0108).
- No loop: an event published by a command has the depth 0, no cause, and the correlation of the command (`Shared\Messaging\CorrelatedCommands`, a middleware of `command.bus`); an event published by a reaction to an event of depth n has the depth n + 1, the same correlation, and the `eventId` it reacted to as its cause; the integration event a translator publishes keeps the depth of its domain event. Beyond `EventLoop::MAX_EVENT_DEPTH` (5), the synchronous delivery throws `EventLoop`, and the worker refuses the event.

## The subscriptions

The reactions are listed in one place (ADR 0097), `apps/api/config/subscriptions.json`, read by Symfony (the parameter `app.subscriptions`) and later by the infrastructure. One entry per subscription, named `<context>-<intent>`, of the context of its reaction:

```json
"prospecting-stop-keeping-exchanges": {
  "context": "prospecting",
  "sources": ["prospecter.prospecting.internal"],
  "events": ["ProspectingStopped.v1"],
  "handler": "Prospecter\\Core\\Prospecting\\Application\\StopKeepingExchanges\\StopKeepingExchangesHandler",
  "inbox": true
}
```

A subscription names its reaction (`handler`), or one per event it listens to (`handlers`, by detail-type, ADR 0104): `reporting-figures` gives each fact to the reaction of its kind:

```json
"reporting-figures": {
  "context": "reporting",
  "sources": ["prospecter.prospecting", "prospecter.personal-data"],
  "events": ["OrganizationFactsChanged.v1", "ExchangeFactsChanged.v1", "ContactFactsChanged.v1"],
  "handlers": {"OrganizationFactsChanged.v1": "…RecordOrganizationFactsHandler", "ExchangeFactsChanged.v1": "…RecordExchangeFactsHandler", "ContactFactsChanged.v1": "…RecordContactFactsHandler"},
  "inbox": false
}
```

Two handlers of one event make two subscriptions. `inbox` (ADR 0102): the worker applies an event once per subscription, for a reaction whose second application would change something; a reaction removing a file goes without, and so does Reporting, the revision of a fact making its second application harmless. A service of the tests may be a subscription declared by its tag `app.subscription` (`Tests\App\Double\FailingReaction`), unknown to the infrastructure. A translator (`Application/Publish…/Publish…Handler`) is no subscription: it stays on the event bus, publishing a domain event for the other contexts. `tests/Architecture/ReactionsAreSubscribedTest` fails when an event handler of the core is neither a translator nor a subscription, when a subscription names an unknown reaction, neither or both of `handler` and `handlers`, handlers for other events than its own, or an event none of its sources publishes, or when a subscription to a source `.internal` is of another context than the event: a domain event never leaves its context (ADR 0046). `StopKeepingExchanges` publishes an `ExchangeLeftWithoutOrganization` per exchange to erase, and `prospecting-erase-exchange` erases each one, so that each erasure fits in its own transaction (`persistence.md`); delivered again for an exchange erased already, it writes nothing. A file removed leaves the storage in a reaction of Prospecting to the event naming it (`prospecting-discard-removed-logo`, `prospecting-discard-removed-attachment`, `prospecting-discard-attachments-of-erased-exchange`), once its removal is written. The order of the reactions is no promise.

## The worker

The function `prospecter-<instance>-api-worker` (`App\Shared\Messaging\IntegrationEventConsumer`, ADR 0101), the package and the environment of the API, its delivery `outbox`, invoked asynchronously by a rule of the bus per subscription (named after it, `infra.md`) with `{"subscription": "<name>", "source": …, "detailType": …, "detail": {…}}`.

- An invocation reads the event (`Invocation`, `EventCodec`), refuses one deeper than 5, opens a unit of work, keeps the inbox of the subscription when it has one (`Inbox`: PK `INBOX#<subscription>`, SK the `eventId`, `expiresAt` 15 days later, in the table of its context, under `attribute_not_exists(SK)`), calls the reaction through its decorators within the cause of the event (what it publishes one level deeper, `Causation`), and commits: the inbox, what the reaction writes, the outbox of what it publishes. An event applied already is found at the commit (`AlreadyApplied`): the invocation ends normally, nothing written. A transaction cancelled by a condition carrying no refusal, or by a conflict with another transaction (`ConditionFailed`), is applied again from its reads, three times at most after a random wait under 100 ms, then thrown (ADR 0104).
- A failure is logged with the `eventId`, the correlation and, for a refusal, its `reason` and `field`, then sorted. Deterministic: a `UseCaseRefused`, a `BusinessException` detected at the commit, an `UnreadableEvent` (an unknown detail-type or version, a detail that does not fit), an `UnknownSubscription` (or an event the subscription does not listen to), an `EventLoop`: the worker puts the invocation in the queue `integration-failures` (`IntegrationFailures`, in the envelope of the failures of Lambda, `requestContext.condition` `Refused`) and ends normally. Any other is thrown: Lambda retries twice, then puts it in the same queue. A reaction with nothing left to do (its object gone) ends without throwing; one missing an earlier fact throws, to be retried.
- `bin/console app:integration:redrive` invokes the worker again, asynchronously, with the `requestPayload` of each message of the queue, then deletes it; a message keeping none is left there, the command failing. An event refused is redriven once what refused it is corrected and deployed.
