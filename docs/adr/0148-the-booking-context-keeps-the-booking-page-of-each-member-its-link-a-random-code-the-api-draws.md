# 0148. The Booking context keeps the booking page of each member, its link a random code the API draws

- Date: 2026-11-18
- Type: architecture
- Status: Proposed
- Implemented by: `packages/core/src/Booking`, `packages/core/src/Connectors/Contract/Service/WhichHaveAConnectedCalendar`, `packages/core/features/regler-mes-plages.feature`, `ajouter-une-periode-inactive.feature`, `proposer-des-canaux.feature`, `activer-et-couper-ma-prise-de-rendez-vous.feature`, `changer-de-lien.feature`, `consulter-ma-prise-de-rendez-vous.feature`, `consulter-une-page-de-prise-de-rendez-vous.feature`

## Context

A prospector wants to be booked by whomever they give a link to (#177 to #179): they set when they may be booked, the days they are away, how they meet (a call, a video call), and open or close their link at once. The booker reaches it without signing in, the first part of Prospecter to be open so. The booking lands in the calendar of the member, which invites the booker: Connectors connects it (ADR 0120). Accounts knows the members, Prospecting their exchanges; none of them is about being booked.

## Decision

- A seventh bounded context, **Booking**: the booking page of each member, their availability and, later, the bookings. Its public page is read without signing in. It uses nothing of the others but their Contract (ADR 0045, 0089): the account of a member from Accounts (`ConsultMembersService`, its port `Hosts`), whether their calendar is connected from Connectors, a new published service (`WhichHaveAConnectedCalendarService`, its port `ConnectedCalendars`: a connection to reconnect is not connected). It publishes nothing so far. It has a table of its own, `<prefix>-booking`, like every context (ADR 0038).
- The **booking page** (`BookingPage`), an aggregate per member, identified by the member: the windows of their week, one a day at most, on the quarter hour, of 15 minutes at least; the time zone they are set in, the IANA name the browser of the member gives with them, the slots being computed in it; the inactive periods, a first and a last day included, without a label; the channels offered; open or closed; the code of its link.
- The **code** of the link is drawn by the API, the client of the core, as it gives the identity of what it creates: 22 random characters of base 62, 128 bits at least, never derived from the member; the core receives it in the command and checks its shape only. The first opening gives the page the code drawn; opened again, the page keeps its link; changing the link takes a new code, the former one leading nowhere.
- A page opens only while the calendar of its member is connected, and offers one channel at least once open; it opens without windows, offering then no slot. A connection lost after the opening does not close it.

## Consequences

- The table, its items (the page, the link of a code to its page) and the anonymous route of the public page are decided with the API (#177, its step of the API).
- A page is set up by the first gesture of its member on it: reading a page never set reads it closed and empty, nothing written.
- The core trusts the randomness of the code it receives: the API draws it with a cryptographic generator.

## Alternatives considered

None was weighed.

## Revisit when

A member wants several windows in a day, several booking pages, or a page shared by several members.
