# 0135. A moved exchange is offered to move back while its former moment is to come

- Date: 2026-11-16
- Type: domain
- Status: Accepted
- Implemented by: `packages/core/src/Prospecting` (`ExchangeIntent::MoveExchangeBack`, `ExchangePossibilities`, `ExchangeSituation::$formerMomentToCome`, `Exchange::isAMomentToCome()`, `ConsultExchange::$movedFrom`, `ExchangeDetailRetriever`), `features/traiter-un-echange-planifie.feature`, `apps/api/src/Exchanges` (`MoveExchangeController`, `ExchangeReader`, `ExchangeDetailPresenter`, `ExchangeDetailLinksResource`), `apps/front/src/features/exchanges/calendar/mutations.ts`

## Context

An exchange moved in the calendar is moved back from its toast, « Annuler », a move to the moment it had (ADR 0119). An exchange whose time has come, still planned, is moved to a moment to come; moved back, it would go to its former moment, passed, which the core refuses (`PlannedExchangeInThePast`). The toast offered the undo all the same: the front cannot decide it without taking a rule of the core (ADR 0027), and the link `move` of the response does not tell it, the exchange moved being to come.

## Decision

The response of a move carries the link `moveBack` (`PATCH /api/exchanges/{id}/moment`, the route of the move) while the moment the exchange was moved from is to come. The core decides it: the reading of an exchange just moved names the moment it was moved from (`ConsultExchange::$movedFrom`), and its state then offers `MoveExchangeBack` to its holder while that moment is to come, with the rule a planned moment follows (`Exchange::isAMomentToCome()`, the one of `ensureToCome`). The API passes the moment the exchange had before the command, and presents the intent as any other (ADR 0081). The toast offers « Annuler » only when the link is there.

## Consequences

- The intent exists on the reading after a move only: the detail read alone, the calendar and the other readings never carry it; the aggregate does not know the former moment, moving back is a move like any other.
- The front follows the link, a new rule of the undo staying in the core.

## Alternatives considered

- Keeping the toast as it was, its undo refused for an exchange whose time had come: weighed by the maintainer, then left for the link.
- Offering the undo on the link `move`: it does not tell it, a late exchange moved to come carrying it while its former moment is passed.

## Revisit when

An undo must outlive the response of the move, or another gesture offers to undo itself.
