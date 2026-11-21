# 0175. The calendar of the host invites the booker of an exchange booked by its public link, Google telling them of each change

- Date: 2026-11-21
- Type: architecture
- Status: Accepted
- Supersedes: for « `sendUpdates=none`, no guest » (its Decision), [0127](0127-connectors-pushes-a-planned-exchange-read-again-at-each-of-its-facts.md): an event inviting a guest is put and cancelled telling them; for « an exchange kept for no organization any more is about to be erased, absent » (`PublishedHeldExchanges`), the exchange of a contact to attach is published, without organization
- Implemented by: `packages/core/features/inviter-qui-me-reserve-par-mon-agenda-google.feature`, `packages/core/src/Connectors/Domain/Model/Guest.php`, `packages/core/src/Connectors/Application/CalendarPusher.php`, `packages/core/src/Connectors/Domain/Model/CalendarEntry.php`, `packages/core/src/Connectors/Domain/Port/ProviderCalendar.php` (`cancel()`), `packages/core/src/Connectors/Domain/Port/ContactNames.php` (`contactDetailsOf()`), `packages/core/src/PersonalData/Contract/Service/NameContacts/ContactNameResponse.php`, `packages/core/src/Prospecting/Contract/Service/ConsultHeldExchanges/HeldExchangeResponse.php`, `apps/api/src/Connectors/Google/GoogleCalendar.php`, `apps/api/src/Connectors/AntiCorruption/AccountsAndPersonalDataMembers.php`

## Context

Whoever books a slot by the public link of a member (ADR 0164) must receive an invitation with the link of the video call, or the number they will be called at, and be told when the member moves or cancels it (#180). The member sends nothing themselves: their calendar at Google does, from their address. The push put every event telling nobody and inviting nobody (ADR 0127). The scope `calendar.app.created` lets Prospecter write the calendar « Prospecter » of the member, theirs: Google accepts guests on its events and sends them its notices with `sendUpdates=all`. Besides, the exchange of a booker nobody knew, a contact to attach kept for no organization until attached (ADR 0146), was not published to Connectors, and so never reached the calendar.

## Decision

- Prospecting publishes whether an exchange was booked by the public link of its holder (`bookedByLink`), and publishes the exchange of a contact to attach, without organization; its event is titled « Visio · Ada Lovelace ».
- The event of a booked exchange invites its contact as its guest (`Guest`), at their e-mail as its holder reads it in clear, which PersonalData gives with their name (`NameContactsService`, its e-mail and its phone only when read in clear). A booked call carries the phone of the guest and the first name of the holder, which PersonalData gives too (`PresentMembersService`): « Alan vous appellera au +33 6 12 34 56 78. ». An exchange the holder planned themselves invites nobody, whatever its contact.
- The adapter puts an event inviting a guest with them as its attendee and `sendUpdates=all`, Google sending the invitation, then an update at each change; any other event telling nobody, as before. An event inviting a guest does not show them the link to the card in Prospecter: its description is the call alone, or nothing (the maintainer's choice).
- Its entry keeps whether it invites a guest. Withdrawn while still to come, cancelled by its holder or by its contact forgotten, the event is cancelled for its guest (`ProviderCalendar::cancel()`, a `DELETE` with `sendUpdates=all`); any other withdrawal, a past event erased, tells nobody. Marking it done or missed pushes nothing.
- The e-mail of the booker goes to Google, in the calendar of the member: the purpose of the booking, to be said in the notice on the personal data of the booking page.

## Consequences

- The booker may receive an update right after the invitation: the link Google creates for a booked video call is kept by Prospecting, which pushes the event again with it as its place. The exchanges pushed again at a connection (`PushUpcomingExchanges`) may send their guests an update with nothing changed, as Google decides.
- A contact forgotten leaves the events done or missed without guest, Google telling nobody.
- The answer of the guest in Google (accepting, declining) is not read: the push stays one way (ADR 0127).
- An entry written before this decision invites nobody: its withdrawal tells nobody.

## Alternatives considered

- A sentence without the name of the host for a booked call (« Vous serez appelé au … »), avoiding to ask PersonalData for it: the journey of QA expects « Michel vous appellera au … », the name was kept; the sentence stays for a host who gave no first name.
- Keeping the link to the card in Prospecter in the description of an event inviting a guest: the guest would have seen it; the maintainer chose to show them none.

## Revisit when

The booker should answer in Prospecter, or Prospecter should send its own e-mails (SES): the invitation would no longer be Google's.
