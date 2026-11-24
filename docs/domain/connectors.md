# Connectors

Connectors connects members to services outside Prospecter. Google Calendar comes first (ADR 0120). The context covers:

- the connections. A member connects through the client that their account created at the provider (ADR 0125);
- the planned exchanges. Connectors pushes them into their holders' calendars at the provider (ADR 0127). It keeps each push's state (ADR 0133);
- the import of e-mails from the members' Gmail mailboxes. The mailboxes notify Prospecter through a topic of the account (ADR 0154).

Code: `packages/core/src/Connectors`.

## Language

- **Client** (`ProviderClient`): an account creates a client at a provider, in its own space there (its Google Cloud). Its members connect through it. It holds:
  - its **credentials** (`ClientCredentials`): the client id and its secret. Both are required (`IncompleteClient`);
  - its **mailbox notifications** (`MailboxNotifications`), only while it has credentials. They name the topic that its members' mailboxes publish their changes to. The topic is named in full (`projects/<project>/topics/<topic>`, `InvalidTopic`). They also name the service account that pushes the changes to Prospecter.

  An account has one client per provider. An administrator of the account configures it.
- **Connection** (`Connection`): it holds the account that a member connected at a provider (`externalAccount`). It holds the lasting access that the member gave (`RefreshToken`). It also holds the id of the account's client that the access was granted to. Its **state** (`ConnectionState`) is **connected**, or **to reconnect** once the provider refused the access. A member has one connection per provider, and the connection is theirs only.
- **Provider** (`Provider`): the outside service. So far, `google` is the only one.
- **Authorization** (`PendingAuthorization`): a member asked a provider for access (`AuthorizationRequest`), and the request waits for the provider's answer. It holds the `state` that the provider gives back, the code verifier (PKCE) and the scopes asked. It is bound to the member for ten minutes.
- **Scope** (`Scope`): what Prospecter asks the provider for. Every connection asks for the calendar. The e-mail import also asks for the mailbox (ADR 0154).
- **Grant** (`Grant`): what the provider granted for a code. It holds the external account, its token, and the scopes granted among those asked. The member may untick a scope on the consent screen.
- **E-mail import** (`MailImport`): the import's state on a connection:
  - **off**;
  - **active** while the member's mailbox notifies Prospecter;
  - **to reconnect** once the provider refused to renew its watch.

  The import also keeps several points:
  - the point in the mailbox history that it read up to;
  - when it last read the history to its end;
  - until when the mailbox is watched (`MailboxWatch`);
  - the furthest point that a notification gave, and when it came.
- **Mailbox e-mail** (`MailboxMail`): a mailbox message as the provider gives it. It holds:
  - its sender and its recipients in « To ». It also holds where the provider files it (its labels), and when it was received or sent;
  - its subject (250 characters at most) and its text without the quoted history (10,000 at most). A longer subject or text is truncated with an ellipsis;
  - its **attachments** (`MailboxAttachment`: their name and size). An image embedded in the body is not an attachment. The first ten attachments of 10 MB at most are imported. The others are left out (ADR 0182).

  An e-mail counts once across all the account's mailboxes. Its `Message-ID` identifies it (ADR 0154). A sent e-mail is not a received e-mail. Neither is an e-mail in the spam, the trash or the drafts. An e-mail labelled sent is a sent e-mail, unless it is in the spam or the trash. A draft is neither received nor sent.
- **Correspondent** (`Correspondent`): the party in the account that an e-mail was exchanged with. Its address finds it. It is a contact, or else an organization itself.
- **Calendar « Prospecter »**: Prospecter creates this calendar in the member's account at the provider. It is the only calendar that Prospecter can touch (`calendar.app.created`). The connection keeps its id once the calendar is created.
- **Held exchange** (`HeldExchange`): an exchange as Prospecting currently describes it. It holds its holder, its status (`ExchangeStatus`), its channel (`Channel`), its moment, its duration and its place or link. It says whether a video call without a link asks for one. It also holds its contact and its organization.
- **Event** (`CalendarEvent`): a planned exchange as its holder finds it in their calendar. It holds when, how and where the exchange happens, and with whom. It holds the card it leads to and the reminder before it. It says whether it asks the provider to create its link (`createsALink`, a Google Meet conference). It also holds its guest. The provider's adapter words the event.
- **Guest** (`Guest`): the person that an event invites, when its exchange was booked through its holder's public link. The guest is the contact who booked it, at their e-mail. For a call, it also holds the phone number that the holder calls them at, and the holder's first name. It also says whether the push notifies the guest. The provider sends the guest the invitation, an update when the event moves, and its cancellation (ADR 0175).
- **Entry** (`CalendarEntry`): an exchange pushed into its holder's calendar. Connectors keeps it to withdraw the event once Prospecting erased the exchange. It also keeps the push's state.
- **Push state** (`SyncState`): it is **pending** until a push reaches the calendar. It is **synced** once a push did. It is **failed** once the provider refused the push for good. It also says since when, and why (`ExchangeSync`).

## Rules

- An administrator of the account configures its client at a provider, or replaces it with another one (`ConfigureClient`). The administrator also removes it (`RemoveClient`). The removal is refused when there is no client (`NoClient`).
  - Only administrators see the client, and only its id. Its secret is never returned (`ConsultMyAccountClients`).
  - Any other member is refused (`NotTheAdministrator`). Accounts tells whether a member is an administrator (`Members`).
  - An account's client serves only that account. There is no instance-wide client.
- A provider is offered only while a client of the member's account serves it.
  - Without such a client, the member is not offered the provider. `ConsultMyConnections` presents the providers offered, with their client's id.
  - An authorization asked of a provider without a client is refused (`ProviderUnavailable`).
  - The code is exchanged with the account's client.
- A member asks for an authorization (`AskForAuthorization`). The client draws its `state` and its verifier. The provider brings them back with a code. While the authorization is waiting, that code connects the member's account (`Connect`).
  - An answer is refused when no authorization of the member awaits it (`UnknownAuthorization`). The authorization may be unknown, expired, or asked by another member.
  - A code that the provider refuses is refused too (`AccessNotGranted`). So is a grant without the calendar (`IncompleteGrant`).
  - An access refused at the provider connects nothing.
  - A provider may not answer the code exchange. Then nothing is written, and the authorization waits until it expires. The member goes through the provider again, because a code serves only once.
- Connecting again replaces the connection's account and token, and the connection is connected again. The former access is revoked at the provider when it was to another account. It is not revoked when it was to the same account, because Google revokes the account's whole grant with one token (`Connection::renew()`).
- A connection is checked with its provider through the account's client (`CheckMyConnections`). The check runs when the connectors page opens.
  - A token that the provider refuses makes the connection **to reconnect**, once (`Connection::check()`). So does a token granted to another client than the account's. That happens when the administrator changed the client.
  - A new secret for the same client keeps the connection connected.
  - Without a client on the account, the connection is not checked. It stays as it was last seen.
  - A provider that does not answer also leaves the connection as it was last seen, and the check fails.
- Disconnecting revokes the access at the provider, then forgets the connection (`Disconnect`). It is refused when there is no connection (`NoConnection`). A failed revocation does not keep the connection.
- Connectors pushes each change to an exchange that is planned, cancelled or erased (`PushExchange`). It reacts to `ExchangeFactsChanged`, and the bus passes it only those facts. It reads the exchange again from Prospecting, so the latest state wins whatever the delivery order (ADR 0127).
  - A planned call, meeting or video call is put into its holder's calendar « Prospecter ». The first push creates the calendar. A push creates it again once its member deleted it.
  - The event names its contact as the holder reads them (`ContactNames`). It reminds the holder at their delay. It has no reminder when the holder turned their reminders off.
  - A done or missed exchange keeps its event as it was. Once its contact is forgotten, though, the event is put again without the contact's name, and it leads to the organization (`UnnameForgottenContact`). This reacts to `ContactForgotten`, and finds the entries by their contact.
  - A cancelled or erased exchange is withdrawn. Forgetting a contact cancels its exchange while that exchange is still to come (ADR 0112).
  - An erased exchange is withdrawn from its entry's calendar. Connectors keeps an entry only for a push that reached the calendar.
  - Nothing is asked of the provider when the holder has no connected connection. Nothing is asked either when their account has no client.
  - A token of another client makes the connection **to reconnect**, and nothing is tried again. So does a token that the provider refuses.
  - A push that the provider refuses for good (`CalendarRefusedThePush`) is kept on its entry as failed. The reaction succeeds (ADR 0133). A refused withdrawal is kept the same way when it has an entry.
  - A push that reaches the calendar makes its entry synced. A push may reach nothing because the connection is to reconnect. Then it makes an existing entry pending.
  - A push writes the connection it read only while that connection is still the one kept, connected at the same moment (`Connection::isTheSameAs()`). Its member may have disconnected or connected again in the meantime. Then the push does not overwrite the connection. It is tried again through the new connection, or not at all (ADR 0133, 0140).
- An exchange booked through its holder's public link invites its contact. The invitation goes to the contact's e-mail, as the holder reads it unmasked (`ContactNames`). A contact without an e-mail is not invited. A booked call tells the contact who calls them, by the holder's first name (`Members`), and at which number.
  - Its event notifies its guest only when the event is new to them or has moved. The provider sends them the invitation, then an update when the event moves.
  - Nothing is sent for a link, a reminder or a name put again. The guest is shown no link into Prospecter.
  - A holder who no longer reads the personal data unmasked invites nobody.
  - An exchange may be withdrawn while still to come, because it was cancelled or its contact was forgotten. Then its event is cancelled for its guest (`ProviderCalendar::cancel()`). Its entry keeps the fact that it invites a guest.
  - An exchange erased once past, done or missed, notifies nobody.
  - An exchange that the holder planned themselves invites nobody. Every other event is put without notifying anyone (ADR 0175).

  The exchange of a contact to attach is pushed too, although no organization keeps it. Its title holds its contact alone.
- A planned video call may ask for its link and have none. Then it is put with a conference request.
  - The request is identified by its exchange. So the provider creates the conference once, however many times the exchange is pushed.
  - The link that the provider returns is published (`VideoCallLinkCreated`, recorded by `Connection::push()`). Prospecting keeps it on the exchange. The exchange is then pushed again with it (ADR 0137).
  - An event that asks for nothing keeps the conference that the provider has.
- Only the holder views an exchange's push state, and only while they have a connection (`ConsultExchangeSync`).
  - The state is the one that the exchange's entry keeps.
  - An exchange that the holder planned, still to come and never pushed, is pending. While their connection is to reconnect, the reason is `toReconnect`.
  - There is no state for another member's exchange, nor for a past exchange never pushed.
  - The holder's connections count their upcoming exchanges that are synced and failed (`ConsultMyConnections`).
- The holder pushes their exchange again (`RetryPush`). Anyone else is refused (`NotTheHolder`). The holder can also push all their failed exchanges again (`RetryFailedPushes`). Each exchange is read again from Prospecting, as the reaction does.
- Some facts push the member's upcoming exchanges (`PushUpcomingExchanges`): the member connected, for the first time or again (`Connected`), or their calendar was created again (`CalendarRecreated`). The push covers the exchanges they still have planned from that moment on. It takes the 40 soonest, read from Prospecting (`ConsultPlannedExchangesService`).
- An administrator of the account names its client's topic and the service account that pushes its notifications (`ConfigureMailboxNotifications`). The administrator also removes them (`RemoveMailboxNotifications`).
  - Both are refused without a client (`NoClient`). Both are refused to any other member (`NotTheAdministrator`).
  - An account's service account is given without a member, to trust a notification (`ConsultMailboxPusher`, ADR 0154).
- The e-mail import is offered while the account's client has a topic. `ConsultMyConnections` presents it with the providers offered. Through the API, it is offered only to a member allowed to manage personal data (ADR 0081).
  - A member asks for their mailbox to be read along with their calendar (`AskToImportMail`). The request is refused without a topic (`MailImportUnavailable`).
  - The provider is asked for the connected account. The API names it to Google (`login_hint`). The provider then brings the member back as for a connection (`Connect`).
  - Another account may grant the access. Then that account is connected instead, as when connecting again, and its mailbox is imported.
  - Once the access is granted, the mailbox is watched through the topic. The watch covers everything except the spam, the trash and the drafts. The import is active from the point where the history stands at that moment (`Connection::importMail()`, `MailImportStarted`).
  - The member may untick the mailbox on the consent screen. Then the import stays off, and the calendar stays connected.
- The member stops the import (`StopMailImport`).
  - The watch is stopped at the provider through the account's client. A failure is ignored, and the watch expires within its week (`MailImportStopped`).
  - The exchanges already imported stay, and the calendar stays connected.
  - Disconnecting stops the import too (`Disconnect`). Connecting another account forgets the former mailbox's import.
- Each day, the watch of every active import is renewed (`RenewMailWatch`, `MailWatchRenewed`). It is renewed through the account's current topic. Nothing is renewed while the account has no topic.
  - A token that the provider refuses makes the import **to reconnect** (`MailImportLost`). So does a token that no longer grants the mailbox. The member then activates the import again.
- A mailbox notifies a change (`NoteMailboxNotification`). This runs without a member, because the provider pushed the notification.
  - The change is recorded on the connection of the account's member who imports that mailbox (`MailboxChanged`).
  - The connection records the furthest history point notified, whatever the notifications' order, and when it came.
  - A mailbox that no member of the account imports records nothing. Neither does an inactive import.
- Once a change was recorded, or the watch renewed, the mailbox history is read from the last point read (`ReadMailboxHistory`, `Connection::readMailboxHistory()`).
  - The point of the last record listed is kept, never a point beyond it.
  - At most 500 messages are read. Each one is imported on its own (`MailboxMessageAdded`). The rest wait for the next reading.
  - The provider may no longer keep the history. Then the history is read again from the date when it was last read to its end. The reading takes the 1,000 newest e-mails received or sent since then. The older ones are lost.
  - A refused token makes the import **to reconnect** (`MailImportLost`).
  - A member who no longer manages personal data has their import stopped (ADR 0154).

  The next notification catches up a lost one. The daily renewal does too.
- Each message read is imported (`ImportMail`) in two cases. PersonalData and Prospecting tell who the correspondents are (`Correspondents`).
  - It is an e-mail received from a contact of the account, or else from the address of one of the account's organizations.
  - It is an e-mail that the member sent to such a contact or organization in « To ». It may be a reply or a new e-mail.

  The import follows these rules:
  - The exchange is told to Prospecting with its subject, its text and its direction, received or sent. It is told once for each correspondent recognized among its recipients (`EmailExchanged`). The reaction itself publishes the event. No domain event carries the text.
  - Each imported attachment is read once from the provider. It is sent to the private storage as an upload for each correspondent, and the event names its upload. The event names the attachments left out by their name and size (`ProviderMailbox::depositAttachment()`, ADR 0182).
  - Some e-mails import nothing: one in the spam, the trash or the drafts, one received from an unknown sender, and one sent to unknown recipients only. Nothing is imported for a person in copy. No contact is ever created.
  - An e-mail that the member sends to themselves or to a member of the account has no correspondent.
  - A message gone in the meantime imports nothing. Neither does an import that is no longer active.
- A member reimports the e-mails that their mailbox already exchanged with a contact or an organization. They do it from the contact's or the organization's card (`CatchUpMailHistory`, ADR 0176).
  - The correspondent's address comes from PersonalData, as the member reads it unmasked. For an organization that the account prospects, it comes from Prospecting (`CorrespondentAddresses`). The address is passed to the provider in its search and is never kept.
  - The search covers the messages exchanged with the address over the previous 12 months. It takes the 200 newest and leaves out the spam, the trash and the drafts. Each message goes to the import as an added message, asynchronously (`Connection::catchUpMailWith()`, `MailboxMessageAdded`).
  - The `ImportMail` rules keep only the sender and the recipients in « To ».
  - An e-mail is logged once in the account. It is never logged again once erased (ADR 0166). So a second reimport logs nothing more.
  - A correspondent that the member does not read is unknown (`UnknownContact`, `UnknownOrganization`). So is a correspondent of another account. This is checked before any connection is looked at.
  - A correspondent without an e-mail has nothing to reimport (`NoAddressToReimport`).
  - An import that is off or to reconnect refuses the reimport (`MailImportNotActive`).
  - A token that the provider refuses makes the import **to reconnect** (`MailImportLost`). The command succeeds.
  - Only a member allowed to manage personal data reimports (`PersonalDataManagersOnly`, checked first). The API refuses the others too, as for the import (ADR 0081).
  - The reimport is offered to such a member only while the import is active and the correspondent has an address (`ConsultMailCatchUpIntents`, `CatchUpGmail`).
- A member views only their own connections (`ConsultMyConnections`). Their external account's address is personal data kept outside PersonalData. It is theirs alone, and it is forgotten when they disconnect (ADR 0120).

## What it publishes

- The service `WhichHaveAConnectedCalendarService`, for Booking. It tells which of the members asked have a connected Google connection. A connection to reconnect does not count (`WhichHaveAConnectedCalendar`, ADR 0148).
- The integration event `VideoCallLinkCreated`. It holds the account, the holder, the exchange and the link that the provider created (ADR 0137). `PublishVideoCallLinkCreated` translates it from the domain event of the same name.
- The integration event `EmailExchanged`, which `ImportMail` publishes. It holds:
  - the account, the member, the provider, the e-mail's identity and the direction;
  - the contact or the organization, the subject, the text and when the e-mail was exchanged (ADR 0154);
  - the id that the provider gives the message in the mailbox (ADR 0174);
  - the uploads of its files and the files left out (ADR 0182).

Its other domain events (`Connected`, `ConnectionLost`, `Disconnected`, `ClientConfigured`, `ClientRemoved`, `CalendarRecreated`, `MailImportStarted`, `MailImportStopped`, `MailImportLost`, `MailWatchRenewed`, `MailboxChanged`, `MailboxMessageAdded`) stay in the context.

It reads:

- from Accounts, a member's account, whether they administer it, their reminder and whether they manage personal data (`Members`, `ConsultMembersService`). From PersonalData, it reads their first name (`PresentMembersService`, ADR 0175);
- from PersonalData and Prospecting, who an address designates (`Correspondents`, `WhichContactsHaveTheseEmailsService`, `WhichOrganizationsHaveTheseEmailsService`);
- from Prospecting, the exchanges (`HeldExchanges`, `ConsultHeldExchangesService`, `ConsultPlannedExchangesService`);
- from PersonalData, the contacts' names, and the e-mail and phone number of a booker it invites (`ContactNames`, `NameContactsService`);
- a contact's address from PersonalData, and an organization's address from Prospecting. It uses them to reimport the e-mails exchanged with them (`CorrespondentAddresses`, `WhichEmailsHaveTheseContactsService`, `WhichEmailsHaveTheseOrganizationsService`, ADR 0176).

It reacts to the exchanges' facts (`ExchangeFactsChanged`) and to forgotten contacts (`ContactForgotten`).

## Features

`connecter-mon-compte-google`, `configurer-le-client-google`, `pousser-mes-echanges-dans-google-agenda`, `synchroniser-mes-echanges-avec-google`, `creer-un-lien-meet-pour-mes-visios`, `inviter-qui-me-reserve-par-mon-agenda-google`, `importer-mes-e-mails-gmail`, `importer-un-e-mail-recu`, `importer-un-e-mail-envoye`, `importer-les-pieces-jointes-d-un-e-mail` (with Prospecting), `rattraper-l-historique-gmail`.

## In the code

- `src/Connectors/Domain`:
  - `Connection` (its `push()`, `withdraw()` and `cancel()`, its `importMail()`, `renewMailWatch()`, `noteMailboxChange()`, `readMailboxHistory()`, `catchUpMailWith()`, `mailOf()` and `stopMailImport()`), `MailboxMail`, `MailboxChanges`, `Correspondent`, `ProviderClient`, `ClientCredentials`, `MailboxNotifications`, `MailImport`, `MailboxWatch`, `Scope`, `Member`, `PendingAuthorization`, `Grant`, `RefreshToken`, `Provider`, `ConnectionState`, `HeldExchange`, `CalendarEvent`, `Guest`, `CalendarEntry`, `ExchangeId`, `ContactId`, `OrganizationId`, `MessageId`, `ExchangeStatus`, `Channel`, `SyncState`;
  - the events `Connected`, `CalendarRecreated`, `VideoCallLinkCreated`;
  - the repositories `ConnectionRepository`, `AuthorizationRepository`, `ProviderClientRepository`, `CalendarEntryRepository`;
  - the ports, which the API implements:
    - `ProviderAccess`: exchange a code through a client's credentials, and tell whether a token still grants access through them. Revoke a token;
    - `ProviderCalendar`: create the calendar « Prospecter ». Put an event, and return the link of the conference it asked for. Remove an event, or cancel it for its guest (`AccessRevoked`, `CalendarGone`, `CalendarRefusedThePush`);
    - `ProviderMailbox`: watch a mailbox through a topic, and stop its watch. List the messages added since a history point (`HistoryExpired` once the history is no longer kept). List the messages received or sent after a moment, or exchanged with an address after a moment. Read a message. Send one of its attachments to the private storage;
    - `Correspondents`, `CorrespondentAddresses`, `Members`, `HeldExchanges` and `ContactNames`.
- `src/Connectors/Application`, in four modules (ADR 0206):
  - `Connection`, a member's connection at a provider: `AskForAuthorization`, `Connect`, `CheckMyConnections`, `Disconnect`, `ConsultMyConnections` (`MyConnection`, the port `MyConnectionsRetriever`), and `WhichHaveAConnectedCalendar` (the service it publishes, ADR 0148);
  - `Client`, the account's client at the provider and its mailbox notifications: `ConfigureClient`, `RemoveClient`, `ConsultMyAccountClients` (the port `AccountClientsRetriever`), and, for ADR 0154, `ConfigureMailboxNotifications`, `RemoveMailboxNotifications` and `ConsultMailboxPusher`;
  - `Calendar`, the exchanges pushed into the holders' calendars, through `CalendarPusher`:
    - `PushExchange`, which its policy sends for `ExchangeFactsChanged` (the subscription `connectors-push-exchange`);
    - `UnnameForgottenContact`, which its policy sends for `ContactForgotten` (`connectors-unname-forgotten-contact`);
    - `PushUpcomingExchanges`, which its policy sends for `Connected` and `CalendarRecreated` (`connectors-push-upcoming-exchanges`);
    - `RetryPush`, `RetryFailedPushes`;
    - `ConsultExchangeSync` (`ExchangeSync`, the port `ExchangeSyncRetriever`);
    - `PublishVideoCallLinkCreated` (the created link's translator, ADR 0137);
  - `MailImport`, the import of e-mails from the members' mailboxes (ADR 0154):
    - `AskToImportMail`, `StopMailImport`;
    - `RenewMailWatch`. The platform gives it each active import (the port `MailImportsRetriever`);
    - `NoteMailboxNotification`;
    - `ReadMailboxHistory`, which its policy sends for `MailboxChanged` and `MailWatchRenewed` (the subscription `connectors-read-mailbox-history`);
    - `ImportMail`, which its policy sends for `MailboxMessageAdded` (`connectors-import-mail`);
    - `CatchUpMailHistory`, `ConsultMailCatchUpIntents`;
  - at the application's root, `AccountClient`, which both `Connection` and `Client` read, the policies of `Policy/` and the ports of `Port/`.
- In the API: the table and its items are in `docs/architecture/persistence.md`, and the endpoints in `docs/architecture/api.md`. Google and its double are in `docs/architecture/local-stack.md` and `infra.md`.
