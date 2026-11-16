# 0134. A calendar holds the exchanges and the to-dos of its holder, a day without an hour never an instant

- Date: 2026-11-16
- Type: architecture
- Status: Accepted
- Supersedes: for the calendar reading the to-dos through `ConsultMyToDos`, [0113](0113-a-to-do-is-an-aggregate-of-prospecting-erased-with-its-contact.md)
- Implemented by: `packages/core/src/Prospecting` (`Due::hasPassedBy()`, `Due::daysOverdueAt()`, `CalendarPeriod::firstDay()`, `lastDay()`, `Overdue`, `ConsultCalendar`, `ConsultExchangesToProcess`, `ToDoListRetriever::dueIn()`), `features/consulter-mon-calendrier.feature`, `features/consulter-mes-echanges-a-traiter.feature`, `apps/api/src/Prospecting/Persistence` (`ToDoKeys::calendar()`, `DynamoToDoRepository`, `DynamoToDoListRetriever`), `apps/api/src/Contacts` (`CalendarQuery`, `ReaderClockQuery`, `CalendarPresenter`, `ExchangesToProcessPresenter`), `apps/front/src/features/exchanges/calendar`, `apps/front/src/features/exchanges/to-process`, `apps/front/src/features/exchanges/reminders`

## Context

The calendar lists the exchanges of its holder, whatever their status, at the moment they happen, on the key `CALENDAR#<member>` read between two instants (ADR 0115). The to-dos are due on a day of the prospector's clock, at an hour of it when said, without a time zone (`Due`, ADR 0113), which expected the calendar to read them through `ConsultMyToDos`: those still to do only, read whole, without a period. The prospector wants their to-dos in the calendar at their due, done or not, in what they have to process once due, and in their reminders; and what is late flagged, a to-do as a planned exchange not processed.

## Decision

- Every to-do is linked into the calendar of its holder whatever its state: `TODO_CALENDAR#<member>` / `TODO#<id>`, in `GSI1` under its due as written, its day, then its hour when said (none before any), then its id; written with the to-do, moved with its due, deleted with it. `app:keys:rebuild` writes it for the to-dos written before.
- A period is read in two queries: the exchanges between the two instants of the `CalendarPeriod`, the to-dos between its first and last days on the clock of its reader (`ToDoListRetriever::dueIn()`). A due is never converted to an instant: a to-do of the 14th is on the 14th whatever the time zone of its reader.
- The moment of a reading is given on the clock of its reader: the API sets the clock of the server in the time zone the SPA sends (`timeZone`, the offset of the period otherwise for the calendar, UTC for what is to process). `ConsultCalendar` presents a `Calendar` of the exchanges and of the to-dos of the period, and the ids of those late (`Overdue`): a planned exchange whose slot has ended, as `ConsultExchangesToProcess` lists it; a to-do not done once its hour, or else its day, has passed (`Due::hasPassedBy()`). `ConsultExchangesToProcess` adds the to-dos still to do of the day of the reading, whatever their hour, and of the days before, the most overdue first, with the days since their day (`Due::daysOverdueAt()`).
- Their text possibly holding personal data, the API shows the to-dos of the calendar and of what is to process to a member with the right to manage it only, as it shows their list (`/api/me/to-dos`).
- The SPA shows the to-dos of a day without an hour in a band above the hours of the day, those with an hour at it without a duration (a height of their own, no slot), each with its box to tick; dragging one changes its due. What is late shows the token `warning` on its edge, an icon and « En retard », never the colour alone, one pulse at the opening of the calendar and none under `prefers-reduced-motion`. A to-do with an hour is reminded at the delay of the exchanges, one without an hour at 9:00 on its day, or at the opening of the SPA after it.

## Consequences

- Each save of a to-do writes one more item; a period of the calendar costs two queries.
- A reader in another time zone finds a to-do at the day and hour written, an exchange at its instant.
- The grid owns a band of the whole day, its height following its items.
- What is to process now holds to-dos: its badge counts them, and its endpoint is read with the time zone of the reader.

## Alternatives considered

- A projection of the calendar, written by a reaction to the facts of Prospecting (like Reporting, ADR 0103): the exchanges and the to-dos are in the same context and the same table, their links written in the same transaction; a projection would lag behind a save the holder reads at once, and copy texts the forgetting of a contact must erase.
- The to-dos in the partition of the exchanges, `CALENDAR#<member>`: sorting a due among instants needs converting it in a time zone when written, wrong once its reader reads elsewhere.
- Reading them through `ConsultMyToDos` (ADR 0113): those still to do only, whole, without a period.
- A continuous blinking of what is late, the first idea of the maintainer: it distracts and fails WCAG 2.2.2; a single pulse at the opening keeps the glance.

## Revisit when

A to-do takes a duration, or a calendar of the team is asked for.
