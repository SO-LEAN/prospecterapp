# 0153. A public booking link offers the free slots of its host, says who they are, and is limited by its code

- Date: 2026-11-18
- Type: architecture
- Status: Accepted
- Supersedes: for what the public page answers and its 404 for a code no page has, [0151](0151-the-booking-pages-in-a-table-of-their-own-their-public-page-read-without-signing-in-their-qr-code-drawn-in-the-browser.md)
- Implemented by: `packages/core/src/Booking/Domain/Policy/{FreeSlots,BookingSuspension}.php`, `packages/core/src/Booking/Domain/Port/{HostAgendas,Hosts}.php`, `packages/core/src/PersonalData/Contract/Service/PresentMembers`, `packages/core/src/PersonalData/Domain/Policy/MemberPresentationPolicy.php`, `packages/core/src/Accounts/Contract/Service/ConsultMembers/MemberResponse.php`, `apps/api/src/Booking/{PublicBookingRoutes,AntiCorruption,Persistence/DynamoRateLimiterStorage}.php`, `apps/api/config/packages/rate_limiter.yaml`, `apps/api/infra/http-api.tf`, `apps/front/src/features/booking`

## Context

The public page of a booking said whether it was open and its channels, nothing of its host (ADR 0151). A booker now sees the free slots of 15 minutes of the host, from tomorrow and for 14 days, and whom they would meet (#178): the name and the picture of the host, their organization. The host is never booked over an exchange they planned, which Prospecting keeps. The page stays read without signing in, by anyone trying codes, and the booking of #179 and #180 will add public routes of Booking.

## Decision

- **The free slots** are a policy of Booking (`FreeSlots`): the windows of the host cut by 15 minutes on their clock, from the day after the reading for 14 days, none on a day of an inactive period, none overlapping an exchange the host still has planned (half-open intervals). The exchanges come from the published `ConsultPlannedExchangesService` of Prospecting (ADR 0133), asked from 8 hours before the reading, the longest exchange Prospecting plans, through the port `HostAgendas`.
- **Suspended**: an open page whose calendar is not connected offers nothing (`BookingSuspension`); the page of the host says so, and the app tells them above every page.
- **Who the host is**: their name and their picture, which PersonalData presents for a member only, never for a person the account prospects (the published `PresentMembersService`, the policy `MemberPresentationPolicy`), and the corporate name of their account, which `MemberResponse` of Accounts now carries; through the port `Hosts`, its anticorruption layer `AccountsAndPersonalDataHosts`.
- **The same answer for any link offering nothing**: a code no page has, a page closed, its former code, a page suspended answer 200 with nothing, not even who the host is; the 404 of an unknown code is gone.
- **Kept a short while**: the page is kept 30 seconds (`Cache-Control: public, max-age=30`); a refusal is kept by nobody.
- **Limited by its code**: every public route of Booking counts the requests of a link, 30 a minute (`symfony/rate-limiter`, a sliding window, a route saying so by its default `_limited_by_its_code`), beyond which a 429 `tooManyRequests` with `Retry-After`; the counts are items of the table of Booking (`RATE_LIMIT#<limiter and code>`, `expiresAt`), shared by every function of the api, written apart from any unit of work. The limit by address is not the app's: the edge of the installation keeps it, and on AWS the HTTP API caps the rate of the route whatever the link (`public_booking_rate_limit`, `public_booking_burst_limit`). The app never reads the address of the client.

## Consequences

- Each public request reads and writes one item more in the table of Booking.
- `ConsultMembersService` reads the accounts of the members it is asked about, once each.
- The shell of the SPA asks the page of the member on every page, for the banner of a suspended link.
- The exchanges of a host are read whole from 8 hours before the reading, not bounded by the 14 days.

## Alternatives considered

- The counts in the memory of each function: every new container a fresh budget, the limit almost symbolic. The maintainer chose the table.
- Only the throttling of the HTTP API, the limit by link left aside: it does not limit a link, nor the routes #179 and #180 add. The maintainer kept the limit by code in the app, and the limit by address at the edge.
- A new published service of Prospecting, the busy periods of a holder over a period: the existing `ConsultPlannedExchangesService` answers the question.

## Revisit when

A booker books (#179): the slot chosen is checked again against the same rules, and its route is limited by the same limiter.
