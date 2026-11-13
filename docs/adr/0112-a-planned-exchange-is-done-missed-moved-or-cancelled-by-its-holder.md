# 0112. A planned exchange is done, missed, moved or cancelled by its holder; a cancelled one is listed from its cancellation

- Date: 2026-11-13
- Type: domain
- Status: Accepted
- Supersedes: for whether a call was missed, a fact of the exchange, and only a call missed, [0074](0074-an-exchange-with-a-contact-is-an-aggregate-of-prospecting.md); for the intents of an aggregate offered without the member, and the intentions of Prospecting taking the member only as the author of what they record, for an exchange, [0081](0081-the-api-checks-the-access-the-core-keeps-the-state.md)
- Implemented by: `packages/core/src/Prospecting` (`ExchangeStatus`, `Cancellation`, `Canceller`, `ExchangeSituation`, `ExchangePossibilities`, `ExchangeChronology`, `Exchange::markDone()`, `markMissed()`, `moveTo()`, `cancel()`, `forgetItsContact()`, `MarkExchangeDone`, `MarkExchangeMissed`, `MoveExchange`, `CancelExchange`), `features/traiter-un-echange-planifie.feature`, `apps/api/src/Prospecting/Persistence` (`ExchangeDocument`, `ExchangeKeys`), `apps/api/src/Exchanges/Controller`

## Context

A planned exchange stayed planned once its moment passed (ADR 0111): nothing said whether it took place. The prospectors want to mark it done, with its report, or missed when the contact did not come or answer, to move it, or to cancel it, a cancelled exchange staying struck through in the timeline. A call noted could already be missed, a fact of the exchange beside its status.

## Decision

- What became of an exchange is its status: planned, done, missed or cancelled. Missed is no longer a fact of a call but a status, open to every channel agreed upon (a call, a meeting, a video call), noted or corrected so as well as marked; an exchange stored done, or without a status, with the fact of a missed call reads as missed, without migration.
- Only its holder handles a planned exchange (`NotTheHolder`, a refusal of the core, 403, the member who acts deciding what an exchange offers and refuses beside the voters of the API, which still check its account), with or without the right to manage personal data: marks it done or missed once its time has come (`NotYetDue`), moves it to a moment to come, its duration kept, or cancels it, saying who called it off if they wish (`Canceller`: the prospector or the contact). Done, missed or cancelled, it no longer changes (`NotUpcoming`). Marked done, its report replaces its agenda and its moment is the one it really took place, never in the future.
- What an exchange offers depends on the member who reads it and on the moment of the reading (`ExchangeSituation`): the query of an exchange takes that moment, as the timelines do.
- A cancelled exchange leaves what is to come for the past at the moment of its cancellation: it is listed there in the timelines (`ExchangeChronology::listedAt()`, the key of its position), still showing the moment it was planned for.
- The contact of a planned exchange forgotten, the exchange still to come is cancelled, whoever holds it; one whose time has come stays for its holder to mark.

## Consequences

- The past and what is to come stay read on the keys of the timelines, without a filter after the query: a cancelled exchange is moved there by its key, written again at each save.
- The figures of Reporting count a missed or cancelled exchange like any (ADR 0111).
- Moving an exchange by dragging it in an agenda is still to write.

## Alternatives considered

Keeping a cancelled exchange at the moment it was planned for, the past then read from the top of the timeline with the planned exchanges still to come filtered out after the query, was weighed while developing: a slice would have needed reading as many more items as there are exchanges to come, which the reading on the keys avoids.

## Revisit when

A reason of cancellation beyond who called it off is asked for, or an exchange cancelled must be planned again as it was.
