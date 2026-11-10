# The bounded contexts

What is in force; the reasons are in the records it names (`docs/adr`), read only to know why or before changing it (ADR 0090).

Accounts, PersonalData and Prospecting (ADR 0030, 0031), and the shared kernel of what they all share (`core.md`). Their language and their rules: `docs/domain/accounts.md`, `personal-data.md`, `prospecting.md`.

## What a context sees of another

- A context never uses another one: it declares a port in its own words (`Prospectors`, `Readers`, `KnownContacts`, `ProspectedOrganizations`), implemented outside the core by an anticorruption adapter (`App\Prospecting\AntiCorruption\AccountsProspectors`, `App\PersonalData\AntiCorruption\AccountsReaders`, `App\Prospecting\AntiCorruption\PersonalDataKnownContacts`, `App\PersonalData\AntiCorruption\ProspectingOrganizations`), doubled in the scenarios by the same code (`tests/Double`). The adapter sees of the other context only what it publishes (ADR 0045): the service its `Contract/Service` declares (ADR 0089: `ConsultMembersService`, `WhichAreContactsService`, `WhichAreProspectedService`), injected by its type, implemented by the handler of the query in its `Application`, its answer kept by `KeptMembers`, `KeptContacts`, `KeptProspected`. A published query asks about a set, a single element being a set of one, and refuses nothing: what is unknown is absent from its answer, the client deciding what an absence means; it answers plain data with only what the others need, checking no caller, fixed by `features/langage-publie.feature`; `tests/Architecture/BoundedContextsTest` fails otherwise. A port of the asking context asks about a set where a use case asks about several elements (`ProspectedOrganizations::prospectedAmong()`). A new question between contexts is a new published service, its scenario, and the rule it answers in the aggregate (`Person::isAContactOf()`).

## What each publishes

| Context | Services (`Contract/Service`, ADR 0089) | Integration events (`Contract/Event`, ADR 0046) |
|---|---|---|
| Accounts | `ConsultMembersService`: who the members are | `AccountOpened`, `MemberRegistered` |
| PersonalData | `WhichAreContactsService`: which persons are contacts of an account | `ContactCollected`, `ContactForgotten` |
| Prospecting | `WhichAreProspectedService`: which organizations an account prospects | `ContactsLeftWithoutOrganization` |

In the API, the anticorruption layers implementing the ports of a context are in `src/<Context>/AntiCorruption` (ADR 0086), the delivery of a context uses nothing of another but its Contract (`api.md`).

## How events travel

Every event leaves its context in the shape of an event of EventBridge (ADR 0097), so that the reactions may later be carried by a bus; for now it is delivered at once, within the command (`INTEGRATION_EVENTS_DELIVERY=sync`). The delivery `outbox` writes it with the command and relays it to the bus of EventBridge (ADR 0098, `persistence.md`), available but not in service: nothing subscribes to the bus yet.

| Field | Value |
|---|---|
| `source` | `prospecter.<context>` (`prospecter.accounts`, `prospecter.personal-data`, `prospecter.prospecting`) for an integration event; `prospecter.<context>.internal` for a domain event |
| `detail-type` | the short name of the class followed by the version of its contract: `ContactCollected.v1` |
| `detail` | `{"eventId": "<uuid v7>", "occurredAt": "<ISO 8601 UTC, microseconds>", "correlationId": "<uuid v7>", "causationId": "<uuid v7 or null>", "depth": 0, "payload": {…}}` |

- The classes are listed explicitly, by source and detail-type, in `App\Shared\Messaging\IntegrationEventTypes` and `DomainEventTypes`; a domain event and an integration event of the same name (`AccountOpened`) are told apart by their source. `EventCodec` writes the payload by the parameters of the constructor: an identity as its string, a moment as the documents write it (`Shared\Persistence\Documents::moment()`), a list element by element, read back by the types of the parameters and their docblocks. The PHP contract carries no identity: the `eventId` belongs to the envelope. A new event class goes in its list, or `tests/Shared/Messaging/EventCodecTest` fails; each survives a round trip through JSON.
- No loop: an event published by a command has the depth 0, no cause, and the correlation of the command (`Shared\Messaging\CorrelatedCommands`, a middleware of `command.bus`); an event published by a reaction to an event of depth n has the depth n + 1, the same correlation, and the `eventId` it reacted to as its cause; the integration event a translator publishes keeps the depth of its domain event. The delivery throws `EventLoop` beyond `SynchronousDelivery::MAX_EVENT_DEPTH` (5).

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

One handler per type of event: two handlers of one event make two subscriptions. A translator (`Application/Publish…/Publish…Handler`) is no subscription: it stays on the event bus, publishing a domain event for the other contexts. `tests/Architecture/ReactionsAreSubscribedTest` fails when an event handler of the core is neither a translator nor a subscription, when a subscription names an unknown reaction or an event no list knows, or when a subscription to a source `.internal` is of another context than the event: a domain event never leaves its context (ADR 0046). `StopKeepingExchanges` publishes an `ExchangeLeftWithoutOrganization` per exchange to erase, and `prospecting-erase-exchange` erases each one, so that each erasure fits in its own transaction once the reactions have one (`persistence.md`); delivered again for an exchange erased already, it writes nothing. Within a command, the order of the reactions is no promise: a translator and the reactions to its integration event run before the internal reactions of the same domain event.
