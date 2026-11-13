# 0115. A calendar is read a period at a time, on a key of its holder; the SPA draws its grid

- Date: 2026-11-13
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/src/Prospecting` (`CalendarPeriod`, `ConsultCalendar`, `CalendarRetriever`), `features/consulter-mon-calendrier.feature`, `apps/api/src/Prospecting/Persistence` (`ExchangeKeys::calendar()`, `DynamoExchangeRepository`, `DynamoCalendarRetriever`), `apps/api/src/Contacts/Controller/ShowCalendarController.php` (`GET /api/me/calendar`), `apps/front/src/features/exchanges/calendar`, `apps/front/src/routes/_app/calendar.tsx`

## Context

A prospector wants a calendar of their exchanges, past and to come, by month, week, day or as a list, which opens fast. The exchanges are listed in the timeline of their contact and of their organization, the past before the moment of the reading, a cancelled exchange from its cancellation (ADR 0111, 0112), and those planned in the agenda of their holder while they are (`AGENDA#<member>`): none lists every exchange of a member at its moment. The SPA has no calendar, and its screens follow the tokens of the design system and take every text from `fr.ts` (ADR 0093).

## Decision

- Every exchange is linked into the calendar of its holder, the member who planned or noted it, whatever its status: `CALENDAR#<member>` / `EXCHANGE#<id>`, listed in `GSI1` under the moment it happens, cancelled or not, then its id; written with the exchange, moved with it, deleted with it. `app:keys:rebuild` writes it for the exchanges written before.
- A calendar is read a period at a time, the days the screen shows, in one query between two positions (`ConsultCalendar`, `GET /api/me/calendar?from=&to=`): six weeks at most, the grid of a month (`CalendarPeriod`), measured on the clock of its reader, a change of time neither lengthening nor shortening it.
- The SPA draws the grid itself, on shadcn/ui and the tokens, with the dates of the browser (`Intl`, `Date`): month, week, day and agenda; each period read is a query of its own in the cache, its neighbours read ahead.

## Consequences

- Each save of an exchange writes one more item, its link in the calendar; the agenda keeps its own key, read in a window of a few hours.
- A calendar of a colleague or of the team would need keys of their own, by account.
- The grid, its keyboard and its time zones are code of the project, tested as such.

## Alternatives considered

- Reading the calendar from the timelines: they are by contact and by organization, a cancelled exchange in them at the moment of its cancellation; a member's exchanges would come from as many queries as contacts.
- FullCalendar, standard edition (MIT): a complete grid, but its stylesheet and its DOM out of the tokens of the design system, its texts from its own locales rather than `fr.ts`; the grid wanted (month, week, day, list, the line of the hour) is small enough to own.

## Revisit when

A calendar of the team is asked for, or moving an exchange by dragging it needs more than the grid owns.
