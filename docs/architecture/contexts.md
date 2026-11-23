# The bounded contexts

This document states the rules in force. The decision records (`docs/adr`) explain why. Read them only for that, or before changing a rule (ADR 0090).

The bounded contexts are:

- Accounts, PersonalData and Prospecting (ADR 0030, 0031);
- Reporting, downstream of them (ADR 0103);
- Connectors, which connects members to outside services (ADR 0120);
- Campaigns, which tags organizations and contacts, downstream of them (ADR 0138);
- Booking, each member's booking page, read without signing in (ADR 0148);
- the shared kernel, holding what several of them share (`core.md`).

Each context's language and rules are in `docs/domain/accounts.md`, `personal-data.md`, `prospecting.md`, `reporting.md`, `connectors.md`, `campaigns.md`, `booking.md`.

## What a context sees of another

- A context never uses another one directly. It declares a port in its own words (`Prospectors`, `Readers`, `KnownContacts`, `ProspectedOrganizations`). An anticorruption adapter outside the core implements it:
  - `App\Prospecting\AntiCorruption\AccountsProspectors`, `App\PersonalData\AntiCorruption\AccountsReaders`, `App\Prospecting\AntiCorruption\PersonalDataKnownContacts`, `App\PersonalData\AntiCorruption\ProspectingOrganizations`;
  - `ProspectingOrganizationsOfContacts`: where contacts work, to compare a contact with their organizations' contacts (ADR 0170);
  - `App\Connectors\AntiCorruption\AccountsAndPersonalDataMembers`: the account of a member who connects, whether they administer it, their reminder, whether they manage personal data, and their first name (ADR 0175);
  - `ProspectingHeldExchanges`, `PersonalDataContactNames`: the exchange Connectors pushes, its contact's name, and the e-mail and phone of a booker it invites (ADR 0175);
  - `PersonalDataAndProspectingCorrespondents`: the contact or organization an e-mail address designates (ADR 0154);
  - `PersonalDataAndProspectingCorrespondentAddresses`: a contact's or an organization's address, to reimport the e-mails exchanged with it;
  - `App\Campaigns\AntiCorruption\AccountsTaggers`, `ProspectingTaggableOrganizations`, `PersonalDataTaggableContacts`: the account of a member who tags, and whether the account prospects an organization or knows a contact;
  - `App\Campaigns\AntiCorruption\ProspectingCampaignExchanges`: the campaign's recipients that Prospecting already logged a campaign exchange with, for the next recipient (ADR 0195);
  - `AccountsCampaigners`, `PersonalDataAndProspectingAddressees`: the account of a member who runs campaigns, and what a message calls a contact (ADR 0144);
  - `App\Prospecting\AntiCorruption\CampaignsCampaignRecipients`, `CampaignsCampaignNames`: whether a contact is a recipient a started campaign still has to handle, and the names of the campaigns in a timeline (ADR 0145);
  - `App\Booking\AntiCorruption\AccountsAndPersonalDataHosts`, `ConnectorsConnectedCalendars`, `ProspectingHostAgendas`: a host's account and its corporate name, how the host presents themselves, whether their calendar is connected, and the exchanges they still have planned (ADR 0148, 0153);
  - `App\PersonalData\AntiCorruption\BookingBookers`, `App\Prospecting\AntiCorruption\BookingBookedAppointments`: who booked an appointment, and what they booked (ADR 0164).

  The scenarios double these ports with the same code (`tests/Double`).
- The adapter sees only what the other context publishes (ADR 0045). That is the service declared in the other context's `Contract/Service` (ADR 0089: `ConsultMembersService`, `WhichAreContactsService`, `WhichAreProspectedService`, `ConsultHeldExchangesService`, `NameContactsService`, `PresentMembersService`, `NameOrganizationsOfContactsService`, `WhichOrganizationsNameTheseContactsService`, `WhichRecipientsArePendingService`, `NameCampaignsService`, `WhichHaveAConnectedCalendarService`, `WhichContactsHaveTheseEmailsService`, `WhichOrganizationsHaveTheseEmailsService`, `WhichEmailsHaveTheseContactsService`, `WhichEmailsHaveTheseOrganizationsService`, `ConsultAppointmentsService`). The service is injected by its type and implemented by the query handler in the context's `Application`. `KeptMembers`, `KeptContacts` and `KeptProspected` keep its answer.
- A published query asks about a set. A single element is a set of one. The query refuses nothing. An unknown element is absent from the answer, and the client decides what the absence means. The query returns plain data, only what the other contexts need, and checks no caller. `features/langage-publie.feature` pins this down, and `tests/Architecture/BoundedContextsTest` fails otherwise.
- When a use case asks about several elements, the asking context's port asks about a set (`ProspectedOrganizations::prospectedAmong()`).
- A new question between contexts means a new published service, its scenario, and the rule that answers it in the aggregate (`Person::isAContactOf()`).

## What each publishes

| Context | Services (`Contract/Service`, ADR 0089) | Integration events (`Contract/Event`, ADR 0046) |
|---|---|---|
| Accounts | `ConsultMembersService`: who the members are, their reminder, their account's corporate name; `ListAccountsService`: the accounts' identities, by slice | `AccountOpened`, `MemberRegistered` |
| PersonalData | `WhichAreContactsService`: which persons are an account's contacts; `WhichContactsHaveTheseEmailsService`: which of an account's contacts have given addresses (ADR 0154); `WhichEmailsHaveTheseContactsService`: contacts' addresses, for a member who reads them in clear; `NameContactsService`: persons' names, titles and job titles as a member reads them, with their e-mail and phone when read in clear (ADR 0175); `PresentMembersService`: members' names and pictures, for those they give a link to; `ListContactFactsService`: the facts of an account's contacts, by slice | `ContactCollected`, `ContactForgotten`, `ContactFactsChanged`, `BookerIdentified` (ADR 0164) |
| Prospecting | `WhichAreProspectedService`: which organizations an account prospects; `WhichOrganizationsHaveTheseEmailsService`: which of an account's organizations have given addresses (ADR 0154); `WhichEmailsHaveTheseOrganizationsService`: the addresses of organizations an account prospects; `NameOrganizationsOfContactsService`: the names of the organizations of an account's contacts, the first one first; `WhichOrganizationsNameTheseContactsService`: which organizations name an account's contacts (ADR 0170); `ConsultHeldExchangesService`: exchanges as their holders hold them, and whether they were booked through the public link (ADR 0175); `ConsultPlannedExchangesService`: the exchanges a holder still has planned from a given moment; `WhichContactsHaveExchangesOfCampaignService`: which of given contacts have an exchange of a campaign (ADR 0195); `ListOrganizationFactsService`, `ListExchangeFactsService`: the facts of an account's organizations and exchanges, by slice | `ContactsLeftWithoutOrganization`, `ContactLeftUnattached` (ADR 0146), `OrganizationFactsChanged`, `ExchangeFactsChanged` |
| Reporting | nothing | nothing |
| Connectors | `WhichHaveAConnectedCalendarService`: which members have their calendar connected; a connection waiting to be reconnected does not count (ADR 0148) | `VideoCallLinkCreated` (ADR 0137), `EmailExchanged` (ADR 0154) |
| Campaigns | `WhichRecipientsArePendingService`: whether a campaign has started, its channel, which contacts it keeps and still has to handle; `NameCampaignsService`: the names of an account's campaigns (ADR 0145) | nothing |
| Booking | `ConsultAppointmentsService`: what was booked through a public link and by whom, as the booker stated it (ADR 0164) | `SlotBooked` (ADR 0164) |

### Facts

A context that counts what another one holds reads its **facts** (ADR 0099). A fact is a snapshot of an aggregate, published each time the aggregate changes:

- `OrganizationFactsChanged`: present, located, the contacts it names, its municipality's code and its address's country (ADR 0142);
- `ExchangeFactsChanged`: present, the exchange's status and moment, its contact and its campaign (ADR 0145);
- `ContactFactsChanged`: present, and how the contact can be reached: e-mail, phone, mobile, LinkedIn, booleans only (ADR 0141).

A translator receives every domain event of the aggregate and publishes the facts before the commit:

- `PublishOrganizationFactsHandler` for `OrganizationEvent`;
- `PublishExchangeFactsHandler` for `ExchangeEvent`;
- `PublishContactFactsHandler` for `PersonEvent`. It publishes nothing for a member's profile, since a member is no contact.

Each fact carries the revision its repository writes (`persistence.md`). The highest revision is the latest. A removed aggregate gives a terminal fact: absent, at revision 0.

The matching service returns the same facts again by slice, so that everything can be counted from the state. Both read one retriever (`OrganizationFactsRetriever`, `ExchangeFactsRetriever`, `ContactFactsRetriever`). `ListAccountsService` tells which accounts there are to count (`AccountListRetriever`).

### Who reads what

- **Reporting** counts the facts (ADR 0103). Its reactions record the facts it receives (`RecordOrganizationFacts`, `RecordExchangeFacts`, `RecordContactFacts`). A rebuild reads them again through its ports `SourceAccounts`, `ProspectingFacts` and `PersonalDataFacts`. Their anticorruption layers ask the services by slice (`App\Reporting\AntiCorruption\AccountsSourceAccounts`, `ProspectingFactsFromProspecting`, `PersonalDataFactsFromPersonalData`). In the API, the subscription `reporting-figures` delivers the facts to it, and `app:reporting:rebuild` counts again (ADR 0104, `persistence.md`).
- **Connectors** takes an exchange's fact as the sign that the exchange changed. It reads the exchange again from `ConsultHeldExchangesService` and pushes it into its holder's calendar. So the latest state wins, whatever order the deliveries arrive in (ADR 0127, `docs/domain/connectors.md`). Once a member has connected, or their calendar was created again, it reads their upcoming exchanges from `ConsultPlannedExchangesService` and pushes them (ADR 0133).
- **Video call links.** A video call that asks for a link (`linkRequested` of `ConsultHeldExchangesService`) is pushed with a conference request. Connectors publishes the link the provider created (`VideoCallLinkCreated`, translated by `PublishVideoCallLinkCreatedHandler` from the connection's domain event). Prospecting keeps it on the exchange (`KeepCreatedVideoCallLink`, the subscription `prospecting-keep-created-video-call-link`), and the exchange is pushed again, its link now in its facts (ADR 0137).
- **Campaigns** forgets an organization's tags at its terminal fact, and a contact's tags once the contact is forgotten (`ContactForgotten`, ADR 0138). It keeps the facts of organizations and contacts (`RecordOrganizationFacts`, `RecordContactFacts`, the subscription `campaigns-record-facts`, from their second version). From them it derives the tags a contact inherits, an organization's geographic tags (ADR 0142) and the tags saying how a contact can be reached. Its rebuild reads them again through its ports `TaggedAccounts`, `OrganizationsAndTheirContacts` and `ContactsReach` (`App\Campaigns\AntiCorruption\AccountsTaggedAccounts`, `ProspectingOrganizationsAndTheirContacts`, `PersonalDataContactsReach`, ADR 0141, `docs/domain/campaigns.md`).
- **Booking.** A slot booked through a public link becomes an exchange of its host (ADR 0164):
  1. Booking publishes `SlotBooked`, identities only.
  2. PersonalData identifies the booker (`IdentifyBooker`, the subscription `personal-data-identify-booker`). It reads who they are from `ConsultAppointmentsService`. The booker is the account contact who has their e-mail. Otherwise, the booker is a new contact to attach, collected for the host from what they typed (ADR 0199). PersonalData then publishes `BookerIdentified`.
  3. Prospecting plans the host's exchange with the booker under the appointment's identity (`PlanBookedAppointment`, `prospecting-plan-booked-appointment`). It reads what was booked from the same service. The person met is to be attached when nobody holds them yet.
  4. Booking forgets the appointment at that exchange's terminal fact, whether a member erased the exchange or the exchange went with its organization (`ForgetAppointmentOfErasedExchange`, the subscription `booking-forget-appointment-of-erased-exchange`, ADR 0171).
- **Campaign exchanges.** Before logging a campaign recipient's exchange, Prospecting asks Campaigns whether the contact is a recipient that a started campaign still has to handle (`WhichRecipientsArePendingService`). Campaigns processes the recipient from the exchange's facts. These facts name the exchange's campaign and its contact (`ProcessRecipientOfCampaignExchange`, the subscription `campaigns-process-recipient`). A timeline and an exchange's detail name its campaign through `NameCampaignsService` (ADR 0145). An erased exchange's terminal fact still names its contact and its campaign, as its erasure states. It also says whether the exchange went with its organization. When a member erased the exchange, Campaigns puts the recipient it processed back to be handled (`ReopenRecipientOfErasedExchange`, the subscription `campaigns-reopen-recipient`, ADR 0152, 0155).
- **Imported e-mails.** Connectors recognizes the sender of a received e-mail by asking PersonalData which contact has that address, and Prospecting which organization has it (`WhichContactsHaveTheseEmailsService`, `WhichOrganizationsHaveTheseEmailsService`). It tells Prospecting about the exchanged e-mail, its subject and its text (`EmailExchanged`, published by `ImportMailHandler`). Prospecting logs it as one of the member's exchanges (`NoteImportedEmail`, the subscription `prospecting-note-imported-email`, ADR 0154). The attached files never travel. Connectors sends them to the private storage, one upload per correspondent, and the event names them. Prospecting joins them and keeps each upload once the exchange is written (`KeepImportedAttachment`, the subscription `prospecting-keep-imported-attachment`, ADR 0182).
- **Reimporting e-mails.** To reimport the e-mails already exchanged with a contact or an organization, Connectors asks PersonalData for the contact's address as the member reads it, and Prospecting for the organization's address (`WhichEmailsHaveTheseContactsService`, `WhichEmailsHaveTheseOrganizationsService`). It then hands each message it finds to the same import. The contact's card and the organization's timeline offer the reimport as Connectors says (`ConsultMailCatchUpIntents`, composed by the API).

In the API, the anticorruption layers that implement a context's ports live in `src/<Context>/AntiCorruption` (ADR 0086). A context's delivery uses nothing of another context but its Contract (`api.md`).

## How events travel

`messaging.md` describes the whole mechanism, with diagrams, its failures and its operations.

Every event leaves its context shaped as an EventBridge event (ADR 0097). The local stack and the instances use the `outbox` delivery (`INTEGRATION_EVENTS_DELIVERY`, ADR 0108). With it, the event is written with the command and relayed to the EventBridge bus (ADR 0098, `persistence.md`). The bus rules hand it to the worker. The worker runs the reactions after the command, each in its own transaction (ADR 0101, [below](#the-worker)). The tests keep the `sync` delivery, and so does an instance that sets it in its `integration_events_delivery` value (`infra.md`). With `sync`, every event is delivered at once, within the command, and a reaction that refuses makes the gesture refused.

| Field | Value |
|---|---|
| `source` | `prospecter.<context>` (`prospecter.accounts`, `prospecter.personal-data`, `prospecter.prospecting`, `prospecter.connectors`) for an integration event; `prospecter.<context>.internal` for a domain event |
| `detail-type` | the class's short name followed by its contract version: `ContactCollected.v1` |
| `detail` | `{"eventId": "<uuid v7>", "occurredAt": "<ISO 8601 UTC, microseconds>", "correlationId": "<uuid v7>", "causationId": "<uuid v7 or null>", "depth": 0, "payload": {…}}` |

- The classes are listed explicitly, by source and detail-type, in `App\Shared\Messaging\IntegrationEventTypes` and `DomainEventTypes`. The source tells apart a domain event and an integration event with the same name (`AccountOpened`).
- `EventCodec` writes the payload from the constructor parameters:
  - an identity as its string;
  - a moment as the documents write it (`Shared\Persistence\Documents::moment()`);
  - a list element by element.

  It reads them back using the parameter types and their docblocks. The PHP contract carries no identity: the `eventId` belongs to the envelope. A new event class goes in its list, or `tests/Shared/Messaging/EventCodecTest` fails. The test checks that each class survives a round trip through JSON.
- A change to an event's payload bumps its version once an outbox or a failure queue may hold the former shape. The instances' outboxes and queues may hold it, since the instances deliver through `outbox` (ADR 0108). The class is written in its current version (`TYPES`) and still read in the former one (`FORMER_TYPES`). A parameter added since then is read as its constructor default, or as null when it has none (ADR 0155). The reaction listens to both versions. For example, Reporting reads `ExchangeFactsChanged.v1`, which has no status, as held.
- No loops. An event published by a command has depth 0, no cause, and the command's correlation (`Shared\Messaging\CorrelatedCommands`, a `command.bus` middleware). An event published by a reaction to an event of depth n has depth n + 1, the same correlation, and the `eventId` it reacted to as its cause. The integration event a translator publishes keeps its domain event's depth. Beyond `EventLoop::MAX_EVENT_DEPTH` (5), the synchronous delivery throws `EventLoop`, and the worker refuses the event.

## The subscriptions

The reactions are listed in one place (ADR 0097), `apps/api/config/subscriptions.json`. Symfony reads it (the `app.subscriptions` parameter), and later the infrastructure reads it too. There is one entry per subscription, named `<context>-<intent>` after its reaction's context. A subscription names its policy (ADR 0203):

```json
"accounts-register-the-founder": {
  "context": "accounts",
  "sources": ["prospecter.accounts.internal"],
  "events": ["AccountOpened.v1"],
  "policy": "Prospecter\\Core\\Accounts\\Application\\Policy\\RegisterTheFounderPolicy",
  "inbox": true
}
```

The events it listens to are those its policy's signature takes. `reporting-figures` names one policy that takes the facts of an organization, an exchange or a contact, and returns the command recording each kind (`RecordFactsPolicy`). A subscription whose reaction is not a policy yet names it (`handler`), or one reaction per event it listens to (`handlers`, by detail-type, ADR 0104):

```json
"prospecting-stop-keeping-exchanges": {
  "context": "prospecting",
  "sources": ["prospecter.prospecting.internal"],
  "events": ["ProspectingStopped.v1"],
  "handler": "Prospecter\\Core\\Prospecting\\Application\\StopKeepingExchanges\\StopKeepingExchangesHandler",
  "inbox": true
}
```

A subscription that listens to some payloads only names them in `payload` (ADR 0127). That is an EventBridge pattern on the detail's payload, with `$or` and the values a field may hold. Its bus rule passes only those, and the `sync` delivery reads the pattern the same way (`Shared\Messaging\PayloadPattern`, `tests/Shared/Messaging/SubscriptionsTest`). `connectors-push-exchange` listens to the facts of exchanges that are planned, cancelled or erased, so most facts ask nothing of the worker. `campaigns-forget-tags-of-unprospected-organization` listens to organizations' terminal facts (`{"present": [false]}`).

- Two handlers of one event make two subscriptions.
- `inbox` (ADR 0102): the worker applies an event once per subscription. A reaction needs it when a second application would change something. These reactions go without one:
  - a reaction that removes a file;
  - Campaigns' reactions that forget a tagging or record a fact;
  - Reporting's reactions, where a fact's revision makes the second application harmless.
- A test service can be a subscription declared by its `app.subscription` tag (`Tests\App\Double\FailingReaction`), unknown to the infrastructure.
- A translator (`Application/Publish…/Publish…Handler`) is not a subscription: it stays on the event bus and publishes a domain event for the other contexts.
- `tests/Architecture/ReactionsAreSubscribedTest` fails when:
  - a core policy is not a subscription;
  - a subscription names an unknown policy, or listens to a detail-type that its policy's signature does not take;
  - a core event handler is neither a translator nor a subscription;
  - a subscription names an unknown reaction, names none or several of `policy`, `handler` and `handlers`, has handlers for events other than its own, or lists an event none of its sources publishes;
  - a subscription to a `.internal` source belongs to a different context than the event: a domain event never leaves its context (ADR 0046).
- `StopKeepingExchanges` publishes one `ExchangeLeftWithoutOrganization` per exchange to erase. `prospecting-erase-exchange` erases each one, so that each erasure fits in its own transaction (`persistence.md`). When the event is delivered again for an exchange already erased, the reaction writes nothing.
- A removed file leaves the storage in a Prospecting reaction to the event naming it (`prospecting-discard-removed-logo`, `prospecting-discard-removed-attachment`, `prospecting-discard-attachments-of-erased-exchange`), once its removal is written.
- The order of the reactions is not guaranteed.

## The worker

The worker is the function `prospecter-<instance>-api-worker` (`App\Shared\Messaging\IntegrationEventConsumer`, ADR 0101). It uses the API's package and environment, with the `outbox` delivery. A bus rule per subscription (named after it, `infra.md`) invokes it asynchronously with `{"subscription": "<name>", "source": …, "detailType": …, "detail": {…}}`.

- An invocation:
  1. reads the event (`Invocation`, `EventCodec`) and refuses one deeper than 5;
  2. opens a unit of work;
  3. keeps the subscription's inbox when it has one (`Inbox`: PK `INBOX#<subscription>`, SK the `eventId`, `expiresAt` 15 days later, in its context's table, under `attribute_not_exists(SK)`);
  4. calls the reaction within the event's cause: it sends a policy's commands through the command decorators, and calls an event handler through its own (what the reaction publishes goes one level deeper, `Causation`);
  5. commits the inbox, what the reaction writes, and the outbox of what it publishes.

  An event already applied is detected at the commit (`AlreadyApplied`). The invocation then ends normally and writes nothing. A transaction can be cancelled by a condition that carries no refusal, or by a conflict with another transaction (`ConditionFailed`). It is then applied again from its reads, at most three times, each after a random wait under 100 ms. After that, the failure is thrown (ADR 0104).
- A failure is logged with the `eventId`, the correlation and, for a refusal, its `reason` and `field`, then sorted:
  - Deterministic: a `UseCaseRefused`, a `BusinessException` detected at the commit, an `UnreadableEvent` (an unknown detail-type or version, a detail that does not fit), an `UnknownSubscription` (or an event the subscription does not listen to), an `EventLoop`. For these, the worker puts the invocation in the `integration-failures` queue (`IntegrationFailures`, in Lambda's failure envelope, `requestContext.condition` `Refused`) and ends normally.
  - Any other failure is thrown: Lambda retries twice, then puts it in the same queue.

  A reaction with nothing left to do (its object is gone) ends without throwing. A reaction missing an earlier fact throws, so that it is retried.
- `bin/console app:integration:redrive` invokes the worker again, asynchronously, with each queue message's `requestPayload`, then deletes the message. A message without one is left in the queue, and the command fails. A refused event is redriven once whatever refused it is fixed and deployed.
