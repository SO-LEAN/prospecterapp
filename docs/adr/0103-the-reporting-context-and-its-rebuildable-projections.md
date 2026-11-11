# 0103. The Reporting context and its rebuildable projections

- Date: 2026-11-11
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/src/Reporting`, `packages/core/features/{tenir-les-chiffres,consulter-les-chiffres,recalculer-les-chiffres}.feature`, `packages/core/tests/Double/{InMemoryFactRepository,InMemoryFiguresRetriever,InMemoryProjectionVersions,NoRecordingsInFlight,AccountsSourceAccounts,SlicedProspectingFacts,SlicedPersonalDataFacts}.php`

## Context

The dashboard counts the organizations of an account on the fly, capped at 1,000: beyond, its figure is wrong, and the front computes the organizations placed on the map from the markers it received. The contexts now publish the facts of their aggregates, by an event each time one changes and by a sliced service (ADR 0099). The figures must be kept from those facts, arriving in any order and maybe more than once, and counted again from the state when they start or went wrong.

## Decision

- A fourth bounded context, **Reporting**, downstream of the three others: it uses nothing of them but their Contract, and publishes nothing.
- It keeps five figures per account: the organizations it prospects, those placed on the map, its contacts, the exchanges it keeps, those of each month, cut in UTC.
- It keeps the last **fact** of each aggregate (`OrganizationFact`, `ExchangeFact`, `ContactFact`) beside the figures: a fact received replaces the known one when its revision is higher, the terminal fact (absent) replacing any other and replaced by none (`FactSuccession`); the figures change by the difference between the two (`FiguresDelta`), written with the fact, a change added to a counter so that two facts recorded at the same time both count.
- The facts and the figures belong to a **version**: the active one is read; a rebuild writes a new one, the next number, out of sight, then makes it the active one and removes the former. A fact received is recorded in every live version, the active one and the one being built, by the code a rebuild uses (`FactRecorder`).
- A rebuild starts its version, waits longer than the timeout of the worker (60 s, the worker 28 s) so that no recording that read the versions before ends after it, reads again the facts of every account from the services of the other contexts, slice after slice, then activates its version. It does not recreate the terminal facts: the services list what exists.
- Each step of a rebuild is a unit of work of its own, each fact its own: its start must be written before the wait to be seen by the recordings, and it writes far more than one unit holds (100 writes). The delivery calls `RebuildFiguresHandler` without `TransactionalCommandHandler`.

## Consequences

- A figure is exact whatever the number of organizations, and the front no longer counts.
- Before the first rebuild there is no active version: the figures read zero and the facts received are kept nowhere; an instance rebuilds once Reporting is put in service.
- A fact redelivered after a rebuild for an aggregate removed before it is counted again, its terminal fact not recreated, until the next rebuild.
- The table, its repositories, the subscriptions of the worker and the command of the rebuild come in a ticket of their own; until then the API leaves Reporting out of its subscriptions.

## Alternatives considered

None: the ticket set the context, its figures, the versions and the rebuild; a rebuild in one unit of work was ruled out by the wait and the ceiling of a transaction.

## Revisit when

A figure needs more than the facts carry, or a rebuild of every account takes longer than a Lambda may run.
