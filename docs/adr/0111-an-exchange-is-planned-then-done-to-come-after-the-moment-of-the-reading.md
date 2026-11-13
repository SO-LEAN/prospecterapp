# 0111. An exchange is planned, then done; what is to come is read after the moment of the reading

- Date: 2026-11-13
- Type: domain
- Status: Accepted
- Supersedes: for an exchange never in the future, noted once it happened, and for the order of a timeline (the most recent first), [0074](0074-an-exchange-with-a-contact-is-an-aggregate-of-prospecting.md)
- Implemented by: `packages/core/src/Prospecting` (`ExchangeStatus`, `Duration`, `Slot`, `Exchange::plannedWithAContact()`, `plannedWithTheOrganization()`, `PlanExchange`, `PlanOrganizationExchange`, `ConsultUpcomingExchanges`, `ConsultOrganizationUpcomingExchanges`, `ConsultOverlappingExchanges`, `AgendaRetriever`), `features/planifier-un-echange.feature`, `apps/api/src/Prospecting/Persistence` (`ExchangeDocument`, `DynamoExchangeRepository`, `DynamoTimelineRetriever`, `DynamoAgendaRetriever`), `apps/api/src/Shared/Persistence/SortKeyRange.php`

## Context

An exchange was a fact of the real world, noted once it happened and never in the future (ADR 0074). The prospectors want to plan a call, a meeting or a video call with a contact or an organization, and see in the timeline what is to come with whom. The exchanges noted so far are in the tables, without a status.

## Decision

- A planned exchange stays an `Exchange` of Prospecting, with the same contact, the same organizations keeping it, the same reading without the right to manage personal data: it gets a status, `ExchangeStatus::Planned`, an exchange noted once it happened being `Done`; an exchange read without a status is done, without migration.
- Only a call, a meeting or a video call is planned (`Channel::mayBePlanned()`), in the future, for a `Duration` of 5 minutes to 8 hours, half an hour when not said; its text is its agenda, its direction outbound. The rule « never in the future » is only that of an exchange noted.
- A planned exchange is held by the member who plans it, its author: planning for a colleague is not offered.
- A timeline is split by the moment of its reading, given by the client of the query: the past until that moment, the most recent first (`ConsultTimeline`, `ConsultOrganizationTimeline`), what is to come after it, the soonest first (`ConsultUpcomingExchanges`, `ConsultOrganizationUpcomingExchanges`), on the same keys of the table, read in both directions; the first exchange to come with a contact is their next exchange.
- While it is planned, an exchange is in the agenda of its holder (`AGENDA#<member>`), which tells the exchanges of theirs a slot overlaps (`Slot`, `ConsultOverlappingExchanges`): a warning, never a refusal.
- A planned exchange is not corrected as a whole (`ExchangePossibilities`): moving it is a gesture of its own, to come.

## Consequences

- A planned exchange whose moment passed reads in the past, still planned, until a gesture says what became of it (done, missed, cancelled); those gestures, and moving it, are still to write.
- The figures of Reporting count a planned exchange like any: they must tell them apart before the next release in production.
- Each save of a planned exchange writes one more item, its link in the agenda, and the save of an exchange no longer planned deletes it.

## Alternatives considered

Splitting the timeline by status rather than by moment was weighed while developing: it needed keys of their own for what is planned, in the timelines of the contacts and of the organizations; the moment reads on the keys there are, in the other direction.

## Revisit when

The gestures following a planned exchange (done, missed, cancelled, moved) are written: a planned exchange whose moment passed may then belong with what is to do rather than with the past.
