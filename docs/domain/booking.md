# Booking

The booking page of each member: when they may be booked, how, and the public link they give to whom they want, the only part of Prospecter read without signing in (ADR 0148, 0153). Code: `packages/core/src/Booking`.

## Language

- **Host**: the member whose booking page it is, the one who is booked.
- **Booking page** (`BookingPage`): one per member, identified by them, theirs alone; set up by their first gesture on it, closed and empty until then.
- **Window** (`Window`): when the host may be booked on a day of the week (`Weekday`), from a time to a later one (`WallClockTime`, HH:MM on their clock), both on the quarter hour (`WindowOffTheQuarterHour`), holding one slot of 15 minutes at least (`WindowTooShort`, « A window holds at least one slot of 15 minutes »); one a day at most (`WeeklyWindows`, `TwoWindowsOnADay`).
- **Time zone of the host** (`HostTimeZone`): the IANA name of the clock the windows are set on (`UnknownTimeZone` otherwise), the one the slots are computed in.
- **Inactive period** (`InactivePeriod`): days the host may not be booked whatever their windows, a first day and a last day included (YYYY-MM-DD), the last on or after the first (`PeriodEndsBeforeItStarts`); no label.
- **Channel** (`Channel`): how a booker meets the host, a call or a video call; the channels offered (`OfferedChannels`), each once.
- **Open** or **closed**: whether the public link offers slots.
- **Code** (`BookingCode`): the code of the public link, 22 random characters of base 62, 128 bits at least, drawn by the API (`InvalidBookingCode` for anything else), never derived from the host.
- **Slot**: 15 minutes the host may be booked, starting on the quarter hour of a window; **free** when no exchange the host still has planned overlaps it (**busy period**, `BusyPeriod`, half open: an exchange ending as a slot starts leaves it free).
- **Suspended**: an open page whose calendar is not connected; it offers nothing until the calendar is connected again (`BookingSuspension`).
- **Host identity** (`HostIdentity`): who the host is to the bookers, their name, their picture and the corporate name of their account.
- **Booker** (`Booker`): who books a slot, as they say it: their first name, their name, their phone (E.164) and their e-mail, all given (`IncompleteBooker`, « A booker gives their first name, last name, phone and e-mail »).
- **Appointment** (`Appointment`): a slot of a host a booker booked by their public link, by a channel, about a subject if any; it holds its slot until it ends, the identity the booker gets should nobody of the account be them given by the client.

## Rules

- The host sets the windows of their week, in the time zone of their clock, all at once, replacing the former ones (`SetWeeklyWindows`).
- The host adds an inactive period (`AddInactivePeriod`) and removes one (`RemoveInactivePeriod`, refused for one they do not have: `UnknownInactivePeriod`); they may not be booked on its days, bounds included (`BookingPage::isUnavailableOn()`).
- The host chooses the channels they offer (`OfferChannels`); an open page offers one at least (`NoChannelOffered`, « Booking offers at least one channel »).
- The host opens their page (`OpenBooking`) only while their calendar is connected, which will invite the booker (`NoConnectedCalendar`, « Booking needs a connected calendar, which invites the booker »; a connection to reconnect is not connected), and while it offers a channel; without windows it opens, offering no slot. The first opening gives it the code the API drew; opened again, it keeps its link.
- The host closes it at once (`CloseBooking`): the public link offers nothing, the settings are kept. A page opens only while closed and closes only while open (`BookingPagePossibilities`).
- The host changes the link of a page that has one (`ChangeBookingLink`, refused before the first opening: `BookingPageWithoutLink`): the code drawn replaces it, the former one leading nowhere.
- The host reads their page on their clock (`ConsultMyBookingPage`): its windows and their time zone, the inactive periods not ended before the day of the reading, the soonest first, its channels, its code, whether it is open, whether their calendar is connected, whether it is suspended, and what it offers.
- Anyone holding a link reads the page it leads to, without signing in, at a moment (`ConsultPublicBookingPage`): who the host is, their channels, their time zone, and the 14 days from the day after the reading on the clock of the host, each with its free slots (`FreeSlots`): the windows cut by 15 minutes, none on a day of an inactive period, none overlapping an exchange the host still has planned (ADR 0153). A code no page has now, a page closed or suspended answer the same: no slot, nothing of the host, not even why.

- The link of a host who may not hold the contacts their bookers become, a member without the right to manage personal data, offers nothing and books nothing (`LinkAvailability`, ADR 0146, 0164).
- Anyone holding a link books a slot without signing in (`BookSlot`, ADR 0164), by the page it leads to (`BookingPage::book`, an `AppointmentRequest` against the `HostAgenda`): a link offering nothing books nothing (`BookingUnavailable`, « This booking link offers no slot »), a channel it does not offer is refused (`ChannelNotOffered`), a slot it does not offer free, or taken meanwhile by another booker, too (`SlotNotFree`, « This slot was just taken »), and a booker with an appointment to come with the host, by the same e-mail whatever its case, is told when it is, on the clock of the host (`AlreadyBooked`, « You already have an appointment »). The appointments to come of a host are busy periods as their exchanges are (`BusyPeriods`): a slot booked is no longer offered. The booking becomes an exchange the host planned, with a contact of the account or one to attach (`docs/architecture/contexts.md`).

## What it publishes

`SlotBooked` (the appointment, the account, the host, the identity of the booker) and the service `ConsultAppointmentsService` (what was booked, by whom), which PersonalData and Prospecting read to identify the booker and plan the exchange (ADR 0164). Its other domain events (`WindowsSet`, `InactivePeriodAdded`, `InactivePeriodRemoved`, `ChannelsOffered`, `BookingOpened`, `BookingClosed`, `BookingLinkChanged`) stay in the context. It reads the account of a member and its corporate name from Accounts and how the member presents themselves from PersonalData (`Hosts`, `ConsultMembersService`, `PresentMembersService`), whether their calendar is connected from Connectors (`ConnectedCalendars`, `WhichHaveAConnectedCalendarService`), and the exchanges they still have planned from Prospecting (`HostAgendas`, `ConsultPlannedExchangesService`, asked from 8 hours before the reading, the longest exchange Prospecting plans).

## Features

`regler-mes-plages`, `ajouter-une-periode-inactive`, `proposer-des-canaux`, `activer-et-couper-ma-prise-de-rendez-vous`, `changer-de-lien`, `consulter-ma-prise-de-rendez-vous`, `consulter-une-page-de-prise-de-rendez-vous`, `reserver-un-creneau`.

## In the code

- `src/Booking/Domain`: `BookingPage`, `Window`, `WeeklyWindows`, `Weekday`, `WallClockTime`, `HostTimeZone`, `InactivePeriod`, `InactivePeriodId`, `Channel`, `OfferedChannels`, `BookingCode`, `BookingPageIntent`, `Availability`, `BusyPeriod`, `BookableDay`, `HostIdentity`, `Booker`, `Appointment`, `AppointmentId`; `AppointmentRequest`, `HostAgenda`; the services `BusyPeriods` and `LinkAvailability`; the policies `BookingPagePossibilities`, `FreeSlots`, `BookingSuspension`; the repositories `BookingPageRepository` and `AppointmentRepository`; the ports `Hosts`, `ConnectedCalendars` and `HostAgendas`, implemented by the API.
- `src/Booking/Application`: `SetWeeklyWindows`, `AddInactivePeriod`, `RemoveInactivePeriod`, `OfferChannels`, `OpenBooking`, `CloseBooking`, `ChangeBookingLink`, through `BookingPages` (the page of a host, set up by their first gesture); `ConsultMyBookingPage` (`MyBookingPage`, the port `MyBookingPageRetriever`), `ConsultPublicBookingPage` (`PublicBookingPage`, `PublicHost`, the port `PublicBookingPageRetriever` reading a `BookingSchedule`); `BookSlot`; `ConsultAppointments`, the published service; `PublishSlotBooked`, its translator.
