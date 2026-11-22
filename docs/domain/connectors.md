# Connectors

Connectors connects members to services outside Prospecter, Google Calendar first (ADR 0120). It covers:

- the connections, made through the client each account created at the provider (ADR 0125);
- the planned exchanges, pushed into their holders' calendars there (ADR 0127), each with the state of its push (ADR 0133);
- the import of the e-mails of the members' mailboxes, Gmail, notified through a topic of the account (ADR 0154).

Code: `packages/core/src/Connectors`.

## Language

- **Client** (`ProviderClient`): what an account created at a provider, in its own space there (its Google Cloud), for its members to connect through. It holds:
  - its **credentials** (`ClientCredentials`): the client id and its secret, both required (`IncompleteClient`);
  - while it has them, its **mailbox notifications** (`MailboxNotifications`): the topic its members' mailboxes publish their changes to, named in full (`projects/<project>/topics/<topic>`, `InvalidTopic`), and the service account that pushes them to Prospecter.

  One per account and provider, configured by an administrator of the account.
- **Connection** (`Connection`): the account a member connected at a provider (`externalAccount`), the lasting access it gave (`RefreshToken`), and the id of the account's client it was granted to. Its **state** (`ConnectionState`) is **connected**, or **to reconnect** once the provider refused the access. One per member and provider, and only theirs.
- **Provider** (`Provider`): the outside service. So far, `google` is the only one.
- **Authorization** (`PendingAuthorization`): what a member asked a provider (`AuthorizationRequest`), waiting for its answer: the `state` the provider gives back, the code verifier (PKCE) and the scopes asked. It is bound to the member for ten minutes.
- **Scope** (`Scope`): what Prospecter asks the provider for: the calendar, for every connection; the mailbox, for the e-mail import (ADR 0154).
- **Grant** (`Grant`): what the provider granted for a code: the external account, its token, and the scopes it granted among those asked. The member may untick one on the consent screen.
- **E-mail import** (`MailImport`): its state on a connection:
  - **off**;
  - **active** while the member's mailbox notifies Prospecter;
  - **to reconnect** once the provider refused to renew its watch.

  It also keeps the point in the mailbox history it read up to, when it last read the history to its end, until when the mailbox is watched (`MailboxWatch`), and the furthest point a notification gave, with when.
- **Mailbox e-mail** (`MailboxMail`): a mailbox message as the provider gives it:
  - its sender, its recipients in « To », where the provider files it (its labels), and when it was received or sent;
  - its subject (250 characters at most) and its text without the quoted history (10,000 at most), each truncated with an ellipsis;
  - its **attachments** (`MailboxAttachment`: their name and size; an image embedded in the body is none). The first ten of 10 MB at most are imported, the others are left out (ADR 0182).

  An e-mail is one across all the account's mailboxes, identified by its `Message-ID` (ADR 0154). A sent e-mail, or one in the spam, the trash or the drafts, is not a received e-mail. An e-mail labelled sent, and neither in the spam nor in the trash, is a sent e-mail. A draft is neither.
- **Correspondent** (`Correspondent`): who in the account an e-mail was exchanged with, found by its address: a contact, or else an organization itself.
- **Calendar « Prospecter »**: the calendar Prospecter creates in the member's account at the provider, and the only one it can touch (`calendar.app.created`). Its id is kept with the connection once created.
- **Held exchange** (`HeldExchange`): an exchange as Prospecting currently tells it: its holder, its status (`ExchangeStatus`), its channel (`Channel`), its moment, its duration, its place or link, whether a video call without a link asks for one, its contact and its organization.
- **Event** (`CalendarEvent`): a planned exchange as its holder finds it in their calendar: when, how and where, with whom, the card it leads to, the reminder before it, whether it asks the provider to create its link (`createsALink`, a Google Meet conference), and its guest. The provider's adapter words it.
- **Guest** (`Guest`): whom the event of an exchange booked through its holder's public link invites: the contact who booked it, at their e-mail. For a call, it also holds the phone the holder calls them at and the holder's first name. It also says whether the push tells them. The provider sends them the invitation, an update when the event moves, and its cancellation (ADR 0175).
- **Entry** (`CalendarEntry`): an exchange pushed into its holder's calendar, kept to withdraw it once Prospecting erased it, and the state of its push.
- **Push state** (`SyncState`): **pending** until a push reaches the calendar, **synced** once one did, **failed** once the provider refused it for good. It also says since when, and why (`ExchangeSync`).

## Rules

- An administrator of the account configures its client at a provider, or replaces it with another one (`ConfigureClient`), and removes it (`RemoveClient`, refused when there is none: `NoClient`). Only they see it, and only its id: its secret is never returned (`ConsultMyAccountClients`). Any other member is refused (`NotTheAdministrator`); whether a member is an administrator is read from Accounts (`Members`). An account's client serves only that account: there is no instance-wide client.
- A provider is offered only while a client of the member's account serves it. Without one, the member is not offered the provider (`ConsultMyConnections` presents the providers offered, with their client's id), and an authorization asked of it is refused (`ProviderUnavailable`). The code is exchanged with the account's client.
- A member asks for an authorization (`AskForAuthorization`); the client draws its `state` and its verifier. The provider brings them back with a code, which connects the member's account (`Connect`) while the authorization is waiting.
  - An answer that no authorization of the member awaits (unknown, expired, or asked by another member) is refused (`UnknownAuthorization`).
  - So is a code the provider refuses (`AccessNotGranted`), and a grant without the calendar (`IncompleteGrant`).
  - An access refused at the provider connects nothing. Neither does a provider that does not answer the code exchange: nothing is written, the authorization waits until it expires, and the member goes through the provider again, since a code serves only once.
- Connecting again replaces the connection's account and token, and the connection is connected again. The former access is revoked at the provider when it was to another account, not when it was to the same account: with one token, Google revokes the whole grant of its account (`Connection::renew()`).
- A connection is checked with its provider through the account's client (`CheckMyConnections`, when the connectors page opens). A token the provider refuses, or a token granted to another client than the account's (the administrator changed it), makes it **to reconnect** (`Connection::check()`), once. A new secret for the same client keeps it connected. Without a client on the account, it is not checked and stays as last seen. A provider that does not answer also leaves it as last seen, and the check fails.
- Disconnecting revokes the access at the provider, then forgets the connection (`Disconnect`), refused when there is none (`NoConnection`). A failed revocation does not keep the connection.
- Each change to an exchange that is planned, cancelled or erased is pushed (`PushExchange`, reacting to `ExchangeFactsChanged`; the bus passes it only those facts). The exchange is read again from Prospecting, so the latest state wins whatever the delivery order (ADR 0127).
  - A planned call, meeting or video call is put into its holder's calendar « Prospecter ». The calendar is created at the first push, and again once its member deleted it. The event names its contact as the holder reads them (`ContactNames`) and reminds them at the holder's delay, with no reminder when they turned their reminders off.
  - A done or missed exchange keeps its event as it was. Once its contact is forgotten, though, the event is put again without the contact's name, leading to the organization (`UnnameForgottenContact`, reacting to `ContactForgotten`; the entries are found by their contact).
  - A cancelled or erased exchange is withdrawn (forgetting its contact cancels it while it is still to come, ADR 0112). An erased one is withdrawn from its entry's calendar; an entry is kept only for a push that reached the calendar.
  - Without a connected connection for the holder, or without a client on their account, nothing is asked of the provider.
  - A token of another client, or one the provider refuses, makes the connection **to reconnect**, and nothing is tried again.
  - A push the provider refuses for good (`CalendarRefusedThePush`) is kept on its entry as failed, and the reaction succeeds (ADR 0133). A refused withdrawal is kept the same way when it has an entry.
  - A push that reaches the calendar makes its entry synced. A push that reaches nothing, because the connection is to reconnect, makes an existing entry pending.
  - A push writes the connection it read only while that connection is still the one kept, connected at the same moment (`Connection::isTheSameAs()`). A connection its member disconnected, or connected again, in the meantime is not overwritten: the push is tried again through the new one, or not at all (ADR 0133, 0140).
- An exchange booked through its holder's public link invites its contact, at their e-mail as the holder reads it in clear (`ContactNames`), and nobody without one. A booked call tells them who calls them, the holder's first name (`Members`), and at which number.
  - Its event is put telling its guest only when it is new to them or has moved: the provider sends them the invitation, then an update when it moves. Nothing is sent for its link, a reminder or a name put again, and the guest is shown no link into Prospecter.
  - A holder who no longer reads the personal data in clear invites nobody.
  - Withdrawn while still to come, because it was cancelled or its contact forgotten, it is cancelled for its guest (`ProviderCalendar::cancel()`); its entry keeps that it invites one. Erased once past, done or missed, nobody is told.
  - An exchange the holder planned themselves invites nobody, and every other event is put telling nobody (ADR 0175).

  The exchange of a contact to attach, kept for no organization, is pushed too, titled with its contact alone.
- A planned video call that asks for its link and has none is put with a conference request. The request is identified by its exchange, so that the provider creates the conference once however many times it is pushed. The link the provider returns is published (`VideoCallLinkCreated`, recorded by `Connection::push()`) for Prospecting to keep on the exchange, which is then pushed again with it (ADR 0137). An event that asks for nothing keeps the conference the provider has.
- Only the holder views the push state of an exchange, and only while they have a connection (`ConsultExchangeSync`). It is the state its entry keeps. An exchange they planned, still to come and never pushed, is pending; while their connection is to reconnect, the reason is `toReconnect`. There is nothing for another member's exchange, nor for a past exchange never pushed. Their connections count their upcoming exchanges that are synced and failed (`ConsultMyConnections`).
- The holder pushes their exchange again (`RetryPush`, refused to anyone else: `NotTheHolder`), or all their failed ones (`RetryFailedPushes`). Each is read again from Prospecting, as the reaction does.
- Once the member connected, for the first time or again (`Connected`), or once their calendar was created again (`CalendarRecreated`), the exchanges they still have planned from that moment on are pushed (`PushUpcomingExchanges`): the 40 soonest, read from Prospecting (`ConsultPlannedExchangesService`).
- An administrator of the account names its client's topic and the service account that pushes its notifications (`ConfigureMailboxNotifications`), or removes them (`RemoveMailboxNotifications`). Both are refused without a client (`NoClient`) and to any other member (`NotTheAdministrator`). An account's service account is given without a member, to trust a notification (`ConsultMailboxPusher`, ADR 0154).
- The e-mail import is offered while the account's client has a topic (`ConsultMyConnections` presents it with the providers offered). Through the API, it is offered only to a member allowed to manage personal data (ADR 0081).
  - A member asks for their mailbox to be read along with their calendar (`AskToImportMail`, refused without a topic: `MailImportUnavailable`). The provider is asked for the connected account (the API names it to Google, `login_hint`), then brings the member back as for a connection (`Connect`).
  - Granted by another account, that account is connected instead, as when connecting again, and its mailbox is imported.
  - Once granted, the mailbox is watched through the topic, all but the spam, the trash and the drafts, and the import is active from where the history stands at that moment (`Connection::importMail()`, `MailImportStarted`).
  - When the member unticked the mailbox on the consent screen, the import stays off and the calendar stays connected.
- The member stops the import (`StopMailImport`). The watch is stopped at the provider through the account's client; a failure is ignored, and the watch expires within its week (`MailImportStopped`). The exchanges already imported stay, and the calendar stays connected. Disconnecting stops the import too (`Disconnect`), and connecting another account forgets the former mailbox's import.
- Each day, the watch of every active import is renewed (`RenewMailWatch`, `MailWatchRenewed`) through the topic the account currently has; nothing is renewed while it has none. A token the provider refuses, or one that no longer grants the mailbox, makes the import **to reconnect** (`MailImportLost`), and the member activates it again.
- A change notified by a mailbox (`NoteMailboxNotification`, without a member: the provider pushed it) is recorded on the connection of the account's member who imports that mailbox: the furthest history point notified, whatever the order of the notifications, and when (`MailboxChanged`). A mailbox no member of the account imports, or an inactive import, records nothing.
- Once a change was recorded, or the watch renewed, the mailbox history is read from the last point read (`ReadMailboxHistory`, `Connection::readMailboxHistory()`).
  - The point of the last record listed is kept, never a point beyond it.
  - At most 500 messages are read, each to import on its own (`MailboxMessageAdded`); the rest wait for the next reading.
  - A history the provider no longer keeps is read again by the date of its last reading to its end: the 1,000 newest e-mails received or sent since then. The older ones are lost.
  - A refused token makes the import **to reconnect** (`MailImportLost`).
  - A member who no longer manages personal data has their import stopped (ADR 0154).

  A lost notification is caught up by the next one, or by the daily renewal.
- Each message read is imported (`ImportMail`) when it is an e-mail received from a contact of the account, or else from the address of one of the account's organizations, or an e-mail the member sent, a reply or a new one, to such a contact or organization in « To » (`Correspondents`, asked of PersonalData and Prospecting).
  - The exchange is told to Prospecting with its subject, its text and its direction, received or sent, once for each correspondent recognized among its recipients (`EmailExchanged`, published by the reaction itself; no domain event carries the text).
  - Each imported attachment is read once from the provider and sent to the private storage as an upload for each correspondent; the event names its upload. The event names those left out by their name and size (`ProviderMailbox::depositAttachment()`, ADR 0182).
  - There is nothing to import from an e-mail in the spam, the trash or the drafts, received from an unknown sender, or sent to unknown recipients only, or for a person in copy. No contact is ever created.
  - An e-mail the member sends to themselves or to a member of the account has no correspondent.
  - A message gone in the meantime, or an import no longer active, imports nothing.
- A member reimports, from a contact's or an organization's card, the e-mails their mailbox already exchanged with it (`CatchUpMailHistory`, ADR 0176).
  - The correspondent's address is read from PersonalData, as the member reads it in clear, or from Prospecting for an organization the account prospects (`CorrespondentAddresses`). It is passed to the provider in its search and never kept.
  - The messages exchanged with it over the previous 12 months, the 200 newest, except the spam, the trash and the drafts, are each given to the import as an added message (`Connection::catchUpMailWith()`, `MailboxMessageAdded`), asynchronously. The `ImportMail` rules keep only the sender and the recipients in « To ». An e-mail is logged once in the account, and never again once erased (ADR 0166), so reimporting again logs nothing more.
  - A correspondent the member does not read, or one of another account, is unknown (`UnknownContact`, `UnknownOrganization`), before any connection is looked at.
  - A correspondent without an e-mail has nothing to reimport (`NoAddressToReimport`).
  - An import that is off or to reconnect refuses it (`MailImportNotActive`).
  - A token the provider refuses makes the import **to reconnect** (`MailImportLost`), and the command succeeds.
  - Only a member allowed to manage personal data reimports (`PersonalDataManagersOnly`, checked first); the API refuses the others too, as for the import (ADR 0081). The reimport is offered to them only while the import is active and the correspondent has an address (`ConsultMailCatchUpIntents`, `CatchUpGmail`).
- A member views only their own connections (`ConsultMyConnections`). The address of their external account is personal data kept outside PersonalData: it is theirs alone, and forgotten when they disconnect (ADR 0120).

## What it publishes

- The service `WhichHaveAConnectedCalendarService`, for Booking: which of the members asked have their Google connection connected; a connection to reconnect does not count (`WhichHaveAConnectedCalendar`, ADR 0148).
- The integration event `VideoCallLinkCreated` (the account, the holder, the exchange, the link the provider created, ADR 0137), translated from the domain event of the same name (`PublishVideoCallLinkCreated`).
- The integration event `EmailExchanged`, published by `ImportMail`: the account, the member, the provider, the e-mail's identity, the direction, the contact or the organization, the subject, the text and when it was exchanged (ADR 0154); the id the provider gives the message in the mailbox (ADR 0174); the uploads of its files and those left out (ADR 0182).

Its other domain events (`Connected`, `ConnectionLost`, `Disconnected`, `ClientConfigured`, `ClientRemoved`, `CalendarRecreated`, `MailImportStarted`, `MailImportStopped`, `MailImportLost`, `MailWatchRenewed`, `MailboxChanged`, `MailboxMessageAdded`) stay in the context.

It reads:

- from Accounts, a member's account, whether they administer it, their reminder and whether they manage personal data (`Members`, `ConsultMembersService`); and from PersonalData, their first name (`PresentMembersService`, ADR 0175);
- from PersonalData and Prospecting, who an address designates (`Correspondents`, `WhichContactsHaveTheseEmailsService`, `WhichOrganizationsHaveTheseEmailsService`);
- from Prospecting, the exchanges (`HeldExchanges`, `ConsultHeldExchangesService`, `ConsultPlannedExchangesService`);
- from PersonalData, the contacts' names, and the e-mail and phone of a booker it invites (`ContactNames`, `NameContactsService`);
- a contact's address from PersonalData and an organization's from Prospecting, to reimport the e-mails exchanged with them (`CorrespondentAddresses`, `WhichEmailsHaveTheseContactsService`, `WhichEmailsHaveTheseOrganizationsService`, ADR 0176).

It reacts to the exchanges' facts (`ExchangeFactsChanged`) and to forgotten contacts (`ContactForgotten`).

## Features

`connecter-mon-compte-google`, `configurer-le-client-google`, `pousser-mes-echanges-dans-google-agenda`, `synchroniser-mes-echanges-avec-google`, `creer-un-lien-meet-pour-mes-visios`, `inviter-qui-me-reserve-par-mon-agenda-google`, `importer-mes-e-mails-gmail`, `importer-un-e-mail-recu`, `importer-un-e-mail-envoye`, `importer-les-pieces-jointes-d-un-e-mail` (with Prospecting), `rattraper-l-historique-gmail`.

## In the code

- `src/Connectors/Domain`:
  - `Connection` (its `push()`, `withdraw()` and `cancel()`, its `importMail()`, `renewMailWatch()`, `noteMailboxChange()`, `readMailboxHistory()`, `catchUpMailWith()`, `mailOf()` and `stopMailImport()`), `MailboxMail`, `MailboxChanges`, `Correspondent`, `ProviderClient`, `ClientCredentials`, `MailboxNotifications`, `MailImport`, `MailboxWatch`, `Scope`, `Member`, `PendingAuthorization`, `Grant`, `RefreshToken`, `Provider`, `ConnectionState`, `HeldExchange`, `CalendarEvent`, `Guest`, `CalendarEntry`, `ExchangeId`, `ExchangeStatus`, `Channel`, `SyncState`;
  - the events `CalendarToFill` (`Connected`, `CalendarRecreated`), `VideoCallLinkCreated`;
  - the repositories `ConnectionRepository`, `AuthorizationRepository`, `ProviderClientRepository`, `CalendarEntryRepository`;
  - the ports, implemented by the API:
    - `ProviderAccess`: exchange a code and tell whether a token still grants access, through a client's credentials; revoke a token;
    - `ProviderCalendar`: create the calendar « Prospecter »; put an event, returning the link of the conference it asked for; remove it, or cancel it for its guest (`AccessRevoked`, `CalendarGone`, `CalendarRefusedThePush`);
    - `ProviderMailbox`: watch a mailbox through a topic and stop its watch; list the messages added since a history point (`HistoryExpired` once the history is no longer kept), or received or sent after a moment, or exchanged with an address after a moment; read a message; send one of its attachments to the private storage;
    - `Correspondents`, `CorrespondentAddresses`, `Members`, `HeldExchanges` and `ContactNames`.
- `src/Connectors/Application`:
  - `AskForAuthorization`, `Connect`, `CheckMyConnections`, `Disconnect`, `ConsultMyConnections` (`MyConnection`, the port `MyConnectionsRetriever`), `ConfigureClient`, `RemoveClient`, `ConsultMyAccountClients` (`AccountClient`, the port `AccountClientsRetriever`);
  - through `CalendarPusher`: `PushExchange` (the reaction to `ExchangeFactsChanged`, the subscription `connectors-push-exchange`), `UnnameForgottenContact` (the reaction to `ContactForgotten`, `connectors-unname-forgotten-contact`), `PushUpcomingExchanges` (the reaction to `Connected` and `CalendarRecreated`, `connectors-push-upcoming-exchanges`), `RetryPush`, `RetryFailedPushes`;
  - `PublishVideoCallLinkCreated` (the translator of the created link, ADR 0137);
  - `ConsultExchangeSync` (`ExchangeSync`, the port `ExchangeSyncRetriever`);
  - `WhichHaveAConnectedCalendar` (the service it publishes, ADR 0148);
  - ADR 0154: `ConfigureMailboxNotifications`, `RemoveMailboxNotifications`, `ConsultMailboxPusher`, `AskToImportMail`, `StopMailImport`, `RenewMailWatch` (given each active import by the platform, the port `MailImportsRetriever`), `NoteMailboxNotification`, `ReadMailboxHistory` (the reaction to `MailboxChanged` and `MailWatchRenewed`, `MailboxToRead`, the subscription `connectors-read-mailbox-history`), `ImportMail` (the reaction to `MailboxMessageAdded`, `connectors-import-mail`);
  - `CatchUpMailHistory`, `ConsultMailCatchUpIntents`.
- In the API: the table and its items in `docs/architecture/persistence.md`, the endpoints in `docs/architecture/api.md`, Google and its double in `docs/architecture/local-stack.md` and `infra.md`.
