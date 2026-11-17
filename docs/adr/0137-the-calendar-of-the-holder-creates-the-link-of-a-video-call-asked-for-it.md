# 0137. The calendar of the holder creates the link of a video call planned asking for one, Connectors publishing it to Prospecting

- Date: 2026-11-17
- Type: architecture
- Status: Accepted
- Implemented by: `packages/core/src/Prospecting/Domain/Model/ExchangeFacts.php`, `packages/core/src/Prospecting/Application/KeepCreatedVideoCallLink`, `packages/core/src/Connectors/Domain/Model/Connection.php`, `packages/core/src/Connectors/Contract/Event/VideoCallLinkCreated.php`, `packages/core/src/Connectors/Application/PublishVideoCallLinkCreated`, `apps/api/src/Connectors/Google/GoogleCalendar.php`, `apps/api/config/subscriptions.json`, `apps/front/src/features/exchanges/awaited-link.ts`, `apps/front/src/features/exchanges/components/plan-form.tsx`, `packages/core/features/creer-un-lien-meet-pour-mes-visios.feature`

## Context

A member connected to Google plans a video call without its link and wants Prospecter to create a Google Meet link for it, instead of creating it in Google and pasting it back (#96). Connectors pushes each planned exchange into the calendar « Prospecter » of its holder (ADR 0127), keeping where each push stands (ADR 0133); the spike #92 found that Google creates a Meet link when the event is put with `conferenceData.createRequest` and `conferenceDataVersion=1`, under the scope `calendar.app.created`, and answers it at once in `hangoutLink`. The link of a video call belongs to the exchange, in Prospecting (`VideoCallLink`); Connectors published nothing so far, and a context sees another only by what it publishes (ADR 0045, 0046).

## Decision

- **Prospecting keeps that a link is asked**: a video call planned without its link may ask one of the calendar of its holder (`ExchangeFacts::$linkRequested`, `PlanExchange` and `PlanOrganizationExchange`), only a video call, and only while it has no link: a link given, or received, ends the request. `ConsultHeldExchangesService` publishes it (`linkRequested`). Prospecting names no provider: the calendar of the holder is whichever Connectors pushes it to.
- **Connectors asks the provider for it at the push**: a planned video call asking a link and having none is put with the request of a conference (`CalendarEvent::$createsALink`); the adapter of Google adds `conferenceData.createRequest` (`hangoutsMeet`, the id of the exchange as its `requestId`, so that a push again creates no second conference) and `conferenceDataVersion=1`, and answers the `hangoutLink` of the event (`ProviderCalendar::put()` returns the link created, or none). The connection that pushed it records it (`Connection::push()`, the domain event `VideoCallLinkCreated`), translated into the first integration event of Connectors, `Connectors\Contract\Event\VideoCallLinkCreated` (the account, the holder, the exchange, the link).
- **Prospecting keeps the link a reaction receives** (`KeepCreatedVideoCallLink`, the subscription `prospecting-keep-created-video-call-link`): the exchange, still a video call without a link, takes it and asks no more (`Exchange::receiveLink()`, the event `VideoCallLinkReceived`, by its holder); a correction of the system, without an author: its last correction stays that of a member. A link the member gave meanwhile wins. Its facts changed, the exchange is pushed again, with its link now, asking nothing.
- **The SPA offers it and waits for it**: a box « Créer un lien Google Meet » in the form of a video call, offered and checked while the member has Google connected (read from the connections the app already keeps) and no link is written; the detail of the exchange reads it again while a link is awaited (`linkAwaited` of its resource), five times at most, after 1, 2, 4, 8 then 15 seconds, stopping once the link is there or after the last one: never a steady polling of the API, which slowed it in bursts once (#89).

## Consequences

- Connectors publishes an integration event: the table of `contexts.md` names it, and Prospecting reacts to Connectors, the two contexts now answering each other; the chain (planned, pushed, link received, pushed again) stays at depth 3, under the bound of ADR 0097.
- The link comes a few seconds after the planning, through the relay and the worker twice: the SPA waits for it by reading the exchange again; a member whose push fails, or who is not connected, never gets it, the request kept until a push reaches the calendar or a link is given.
- The Meet link removed by a member does not come back: the request ended with the link received.
- Google keeps the conference of an event put again without `conferenceDataVersion` (version 0 ignores the conference of the body): the pushes after the link keep it.

## Alternatives considered

None was weighed beyond what the ticket named.

## Revisit when

A second provider creates links (Teams, Zoom), or a member wants the link without connecting a calendar.
