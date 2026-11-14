# 0119. A planned exchange is moved for a new duration if one is given, else its own

- Date: 2026-11-14
- Type: domain
- Status: Accepted
- Supersedes: for the duration of a planned exchange kept when it is moved, [0112](0112-a-planned-exchange-is-done-missed-moved-or-cancelled-by-its-holder.md)
- Implemented by: `packages/core/src/Prospecting` (`MoveExchange`, `Exchange::moveTo()`, `ExchangeFacts::movedTo()`, `ConsultCalendar`, `Calendar`), `features/traiter-un-echange-planifie.feature`, `features/consulter-mon-calendrier.feature`, `apps/api/src/Exchanges` (`MoveExchangeController`, `MomentInput`), `apps/api/src/Contacts/CalendarPresenter.php`, `apps/front/src/features/exchanges/calendar`

## Context

A planned exchange is moved to a moment to come by its holder, its duration kept (ADR 0112): its duration is said only when it is planned. In the calendar, the prospectors want to drag an exchange to another moment and to stretch it to last longer or shorter, a gesture of the same holder on the same exchange still to come.

## Decision

Moving a planned exchange takes a new duration if one is given, within the bounds of a planned exchange (`Duration`: 5 minutes to 8 hours, `DurationOutOfRange`), else it keeps its own. Stretching it in the calendar is moving it to its own moment for another duration: one intention, `MoveExchange` (`PATCH /api/exchanges/{id}/moment`, `durationInMinutes` beside `occurredAt`), with the rules of a move: by its holder, while it is planned, to a moment to come.

The calendar tells, at the moment of its reading, which of its exchanges its holder may move (`Calendar::$movable`, from `ExchangePossibilities`): each carries then the link `move`, the one the SPA lets drag and stretch.

## Consequences

- An exchange whose time has come, still planned, is moved to a moment to come as before (ADR 0112), dragged in the calendar too; it is not stretched where it is, its moment passed.
- A duration changed alone, without a calendar, would be a move to its own moment.
- Dragging and stretching stay code of the project on the grid it owns (ADR 0115), on the events of the pointer, without a library.

## Alternatives considered

None was weighed: the ticket asked for the stretch to go through the move.

## Revisit when

A duration must change once the time of the exchange has come, or apart from its moment.
