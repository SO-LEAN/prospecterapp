# 0127. Connectors pushes a planned exchange into the calendar of its holder, read again from Prospecting at each of its facts

- Date: 2026-11-15
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/src/Connectors/Application/PushExchange`, `packages/core/features/pousser-mes-echanges-dans-google-agenda.feature`, `packages/core/src/Prospecting/Contract/Service/ConsultHeldExchanges`, `packages/core/src/PersonalData/Contract/Service/NameContacts`, `apps/api/src/Connectors/Google/GoogleCalendar.php`, `docker/nginx/conf.d/google-double.conf`

## Context

A member connected to Google wants each exchange they plan in their « Prospecter » calendar there, moved, cancelled or erased with it, Prospecting being the reference (#94, ADR 0120). Connectors is downstream: it sees Prospecting, PersonalData and Accounts only by what they publish (ADR 0045, 0089). Prospecting already publishes `ExchangeFactsChanged` at each change of an exchange, its status and its moment only, for Reporting (ADR 0099). The worker delivers an event at least once and in no order (ADR 0101). The title of the event in Google names the contact, a personal data the outbox and the queue of the failures keep for days (ADR 0098, 0101) and that must leave Google once the contact is forgotten. The spike #92 settled how Google is pushed to.

## Decision

- **The trigger is `ExchangeFactsChanged`, its payload unchanged**: a reaction of Connectors (`connectors-push-exchange`, with an inbox) takes it only as the sign that an exchange changed, and reads it again from Prospecting through a new published service, `ConsultHeldExchangesService` (the exchanges among these ids: their holder, status, moment, duration, channel, place or link, contact, and the name of the first organization they are kept for; an erased exchange absent). Read at the moment of the reaction, the latest state wins whatever the order of the deliveries: no revision to keep in Connectors, no contract of Prospecting to bump, Reporting untouched.
- **The name of the contact is read from PersonalData at the push**, by a new published service, `NameContactsService` (the names of these contacts as this member reads them: in clear, or masked for a member without the right to read personal data, as PersonalData shows them). No name travels in an event, the outbox or a queue; a forgotten contact cancels its exchanges still to come (ADR 0112), which withdraws them from Google, and is named by nobody since.
- **The reminder of the member** is read from Accounts in its published answer of a member (`MemberResponse::$reminderMinutesBefore`, none when turned off).
- **What the reaction does**: a planned exchange is pushed into the calendar of its holder; done or missed, it is left as it is; cancelled or erased, it is withdrawn. Without a connection of the holder, connected, nor a client of their account, nothing is asked of Google. Connectors keeps an **entry** of each exchange it pushed (`CalendarEntry`: `EXCHANGE#<id>` / `ENTRY#<provider>`, the member and the provider), the only way to know whose calendar an erased exchange left, Prospecting no longer knowing it; an exchange erased without an entry asks nothing.
- **The calendar « Prospecter »** is created by the first push (scope `calendar.app.created`), its id kept with the connection, created again when Google answers that it is gone (the member deleted it).
- **The push** (`ProviderCalendar`, a port of Connectors implemented by `App\Connectors\Google\GoogleCalendar`), as #92 established: the id of the event `p` + the id of the exchange without its dashes, a `PUT` with `status: confirmed`, a `POST` with that id on a 404, a `PUT` again on a 409; a withdrawal a `DELETE`, a 404 or a 410 meaning done; `start.timeZone` `Europe/Paris`, `reminders.useDefault=false` and one `popup` at the delay of the member, `sendUpdates=none`, no guest. The title « <channel> · <contact> (<organization>) », or « <channel> · <organization> » with the organization itself, the place or the link as its location, the description the link to the card of the contact (or of the organization) in the SPA of the instance (`APP_SPA_URL`): the words of the title belong to the adapter, as the front words what the API answers.
- **The failures**: a token Google refuses (`invalid_grant`), or a token of another client than that of the account, makes the connection « to reconnect » (`ConnectionLost`) and the reaction ends, no retry; a 429, a 403 `rateLimitExceeded` or `userRateLimitExceeded`, a 5xx or a network failure is thrown, Lambda retrying (ADR 0101); any other 4xx is a refusal (`CalendarRefusedThePush`), put in the queue of the failures without a retry.
- **The double of Google** answers the calendar too: `Tests\App\Double\GoogleDouble` for the tests of the API, keeping the calendars and their events; nginx for the stack (`google-double.conf`), answering every push and every withdrawal as done; the scenarios double the port (`InMemoryProviderCalendar`).

## Consequences

- Each push costs Google a refresh of the token and one or two calls, a first push one more for the calendar; each change of any exchange of a member connected costs the reaction a reading of Prospecting, an exchange noted after it happened asking Google nothing.
- A change made in Google is overwritten at the next change in Prospecting; a name changed in PersonalData, an organization renamed, reach Google only at the next change of the exchange.
- An exchange pushed then erased while the commit of its entry failed stays in Google: the entry is written by the same unit as the connection, after the push.
- The entries of a member disconnected stay until their exchanges are withdrawn, asking Google nothing then.
- The state of the push shown to the member, a push tried again by hand, and the exchanges to come pushed at the connection are #95; they read the entries.

## Alternatives considered

- `ExchangeFactsChanged` enriched (v3) with the holder, the duration, the channel, the venue and the contact, named by the ticket: Connectors would have kept the last revision pushed of each exchange to ignore an older one delivered after it, and Reporting would have read a new version; the event would still lack the name of the contact. Refused for reading the state again.
- The name of the contact carried by Prospecting in its fact, named by the ticket: Prospecting does not know it, and the outbox and the failures would keep it after the contact is forgotten. Refused for the published service of PersonalData.

## Revisit when

A second provider arrives (Outlook), the pushes meet the quotas of Google, or Connectors needs the history of what it pushed.
