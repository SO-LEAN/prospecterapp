# 0151. The booking pages in a table of their own, their public page read without signing in, their QR code drawn in the browser

- Date: 2026-11-18
- Type: architecture
- Status: Proposed
- Implemented by: `apps/api/src/Booking`, `apps/api/config/packages/security.yaml`, `apps/api/infra/tables.tf`, `apps/front/src/features/booking`, `apps/front/src/routes/booking.$code.tsx`

## Context

The Booking context keeps the booking page of each member, its link a code the API draws (ADR 0148). Anyone holding the link opens the page, without an account: the first route of the API answering without an access token but `GET /api/user-pool`. The member gives the link as they want, printed too: the screen offers it as a QR code to download (#177).

## Decision

- **The table** `<prefix>-booking`, like every context (ADR 0038): the page of a host, PK `BOOKING_PAGE#<member>`, SK `BOOKING_PAGE`; the link of a code to its host, PK `BOOKING_CODE#<code>`, SK `BOOKING_CODE`, put under `attribute_not_exists(PK)` when the code is new, the link of the former code deleted in the same transaction. The public page is read by its code: its link, then the page of its host.
- **The code** is drawn by the API (`App\Booking\BookingCodes`): 22 characters of base 62, each drawn by `random_int`, some 131 bits; the core receives it in the command (ADR 0148).
- **The public page**, `GET /api/booking-pages/{code}` (the code 22 characters of base 62), behind a firewall of its own without security, as `/api/user-pool`; it answers whether the page is open and its channels, nothing of its host. The page of the member, `/api/me/booking-page…`, stays behind the firewall `api`, `ROLE_USER`: every member sets their own, none another's.
- **The QR code** is drawn in the browser by the package `qrcode` (1.5.4, pinned, with `@types/qrcode` 1.5.5): a PNG the member downloads as is; the API knows nothing of it.

## Consequences

- The public page can be asked by anyone guessing codes: 131 bits make guessing hopeless, and nothing is answered of the member.
- The check of the consistency reads nothing of the table of Booking: it keeps nothing derived from another context.
- One more dependency in the front, for one image.

## Alternatives considered

None was weighed.

## Revisit when

The public page needs to say who the host is, or a booker books (#178, #179): what it answers grows.
