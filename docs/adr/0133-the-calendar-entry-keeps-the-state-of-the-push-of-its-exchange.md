# 0133. The calendar entry keeps the state of the push of its exchange, a refusal kept there, the exchanges to come pushed at the connection

- Date: 2026-11-16
- Type: architecture
- Status: Accepted
- Supersedes: for a push Google refuses for good, put in the queue of the failures without a retry, [0127](0127-connectors-pushes-a-planned-exchange-read-again-at-each-of-its-facts.md)
- Implemented by: `packages/core/src/Connectors/Domain/Model/CalendarEntry.php`, `packages/core/src/Connectors/Application/{RetryPush,RetryFailedPushes,PushUpcomingExchanges,ConsultExchangeSync}`, `packages/core/src/Prospecting/Contract/Service/ConsultHeldExchanges/ConsultPlannedExchanges.php`, `apps/api/src/Connectors/Controller/{ConsultExchangeSync,RetryPush,RetryFailedPushes}Controller.php`, `apps/api/src/Consistency/ExchangesInTheCalendars.php`, `apps/front/src/features/exchanges/components/google-sync.tsx`, `packages/core/features/synchroniser-mes-echanges-avec-google.feature`

## Context

A member connected to Google wants to know whether each exchange they planned is in their calendar there, to push again one Google refused, and their exchanges to come pushed when they connect or reconnect, without thinking of it (#95). ADR 0127 pushes an exchange at each of its facts and keeps an entry of each exchange the push reached the calendar with; a push Google refuses for good is a refusal, the reaction undone and its event put in the queue of the failures, where only an operator sees it; a push while the connection is to reconnect reaches nothing and nothing is tried again. An agenda « Prospecter » the member deleted is still served by Google a moment, the pushes of that moment lost (#92). Connectors sees Prospecting only by what it publishes (ADR 0045, 0089).

## Decision

- **The entry keeps the state of the push** (`CalendarEntry`): **synced** at the moment the push reached the calendar, **failed** with the reason and the moment Google refused it for good, **pending** once a push reached nothing, the connection to reconnect; and the moment the exchange starts. A planned exchange to come without an entry is pending. The entry is also listed under its member in `GSI1` (`ENTRIES#<member>`, by the moment its exchange starts), to count their exchanges to come synced or failed and to push again those failed.
- **A push Google refuses for good is kept, not thrown**: the reaction records the refusal on the entry and succeeds, the entry kept even when the push never reached the calendar; a withdrawal refused alike when the exchange has an entry, thrown as before otherwise, nothing keeping where it stands. The member sees it and pushes it again (`RetryPush`, one exchange, by its holder; `RetryFailedPushes`, every failed one of the member), the push reading the exchange again from Prospecting as the reaction does. A failure that may pass (a 429, a 5xx, the network) is still thrown and tried again by Lambda, the state left as it was.
- **The exchanges to come are pushed at the connection**: a reaction of Connectors to its own `Connected` (a first connection and every reconnection), and to `CalendarRecreated` (the calendar created again once Google answered it was gone, the remedy of #92), reads the exchanges its member planned from that moment on through a new published service of Prospecting, `ConsultPlannedExchangesService` (the planned exchanges of a holder from a moment, in the published language of `ConsultHeldExchanges`, beside it in its package, a Contract importing nothing), and pushes the 40 soonest: what one unit of work writes at most (100 items, an entry and its link to its contact per exchange).
- **The state is read per exchange and per member** (`ConsultExchangeSync`, `ConsultMyConnections` counting the synced and the failed to come), by the holder only, with a connection to the provider.
- `app:consistency:check` reports the exchanges to come of a member connected that are not synced, the reconnection or the retry correcting them.

## Consequences

- The queue of the failures no longer receives a push refused for good: the member, who can act on it, sees it instead.
- A push failing on a failure that may pass leaves an entry as it was until Lambda tries it again: an exchange moved meanwhile reads synced a moment.
- A connection pushes the exchanges to come of its member, one call or two per exchange: a member with many waits for them a moment after connecting; beyond the 40 soonest, an exchange stays pending until its next change, its retry, or the next connection, and `app:consistency:check` reports it.
- An exchange planned while the connection was to reconnect is pushed at the reconnection, then withdrawn once erased: the scenario of ADR 0127 saying it was withdrawn from nothing holds only while the connection stays to reconnect.
- An exchange planned in the past and never pushed has no state: only an exchange to come is pending.

## Alternatives considered

None was weighed beyond what the ticket named.

## Revisit when

A second provider arrives, or the pushes at a connection meet the quotas of Google.
