# Booking

Each member's booking page: when they may be booked, how, and the public link they give to whoever they want. It is the only part of Prospecter read without signing in (ADR 0148, 0153). Code: `packages/core/src/Booking`.

## Language

- **Host**: the member who owns the booking page, the one who is booked.
- **Booking page** (`BookingPage`): one per member, identified by the member and theirs alone. The member's first action on it sets it up; until then it is closed and empty.
- **Window** (`Window`): when the host may be booked on a day of the week (`Weekday`), from a time to a later one (`WallClockTime`, HH:MM on the host's clock).
  - Both times fall on the quarter hour (`WindowOffTheQuarterHour`).
  - A window holds at least one 15-minute slot (`WindowTooShort`, « A window holds at least one slot of 15 minutes »).
  - There is at most one window a day (`WeeklyWindows`, `TwoWindowsOnADay`).
- **Host time zone** (`HostTimeZone`): the IANA name of the clock the windows are set on (`UnknownTimeZone` otherwise). The slots are computed in it.
- **Inactive period** (`InactivePeriod`): days the host may not be booked, whatever their windows. It runs from a first day to a last day, both included (YYYY-MM-DD). The last day is on or after the first (`PeriodEndsBeforeItStarts`). It has no label.
- **Channel** (`Channel`): how a booker meets the host, a call or a video call. The offered channels (`OfferedChannels`) list each channel once.
- **Open** or **closed**: whether the public link offers slots.
- **Code** (`BookingCode`): the public link's code. It is 22 random base-62 characters, at least 128 bits, drawn by the API (`InvalidBookingCode` for anything else). It is never derived from the host.
- **Slot**: 15 minutes when the host may be booked, starting on a window's quarter hour. A slot is **free** when no exchange the host still has planned overlaps it. Such an exchange is a **busy period** (`BusyPeriod`), half open: an exchange that ends as a slot starts leaves the slot free.
- **Suspended**: an open page whose calendar is not connected. It offers nothing until the calendar is connected again (`BookingSuspension`).
- **Host identity** (`HostIdentity`): who the host is to the bookers: their name, their picture and their account's corporate name.
- **Booker** (`Booker`): who books a slot, as they state it: their first name, last name, phone (E.164) and e-mail, all of them given (`IncompleteBooker`, « A booker gives their first name, last name, phone and e-mail »).
- **Appointment** (`Appointment`): a host's slot that a booker booked through the public link, by a channel, about a subject if any. It holds its slot until the slot ends or the appointment's exchange is erased. It also holds the identity the client gives the booker, which the booker takes if nobody in the account is them.

## Rules

- The host sets their week's windows all at once, in their clock's time zone. The new windows replace the old ones (`SetWeeklyWindows`).
- The host adds an inactive period (`AddInactivePeriod`) and removes one (`RemoveInactivePeriod`). Removing one they do not have is refused (`UnknownInactivePeriod`). The host may not be booked on the period's days, bounds included (`BookingPage::isUnavailableOn()`).
- The host chooses the channels they offer (`OfferChannels`). An open page offers at least one (`NoChannelOffered`, « Booking offers at least one channel »).
- The host opens their page (`OpenBooking`) only under two conditions:
  - their calendar is connected, since the calendar invites the booker (`NoConnectedCalendar`, « Booking needs a connected calendar, which invites the booker »). A connection waiting to be reconnected is not connected;
  - the page offers a channel.

  A page without windows still opens, and offers no slot. The first opening gives the page the code the API drew. A page opened again keeps its link.
- The host closes their page at once (`CloseBooking`): the public link offers nothing, and the settings are kept. A page opens only while closed and closes only while open (`BookingPagePossibilities`).
- The host changes the link of a page that has one (`ChangeBookingLink`). It is refused before the first opening (`BookingPageWithoutLink`). The newly drawn code replaces the link, and the old one leads nowhere.
- The host views their page on their own clock (`ConsultMyBookingPage`):
  - its windows and their time zone;
  - the inactive periods not ended before the day of viewing, soonest first;
  - its channels and its code;
  - whether it is open, whether their calendar is connected, and whether it is suspended;
  - what it offers.
- Anyone holding a link views the page it leads to at a given moment, without signing in (`ConsultPublicBookingPage`). They see:
  - who the host is, their channels and their time zone;
  - the 14 days from the day after the viewing, on the host's clock, each with its free slots (`FreeSlots`). The free slots are the windows cut into 15 minutes, none on an inactive period's day, and none overlapping an exchange the host still has planned (ADR 0153).

  A code no page currently has, a closed page and a suspended page all get the same answer: no slot, nothing about the host, not even why.

- The link of a host who may not hold the contacts their bookers become, a member without the right to manage personal data, offers nothing and books nothing (`LinkAvailability`, ADR 0146, 0164).
- Anyone holding a link books a slot without signing in (`BookSlot`, ADR 0164), through the page the link leads to (`BookingPage::book`, an `AppointmentRequest` against the `HostAgenda`):
  - a link offering nothing books nothing (`BookingUnavailable`, « This booking link offers no slot »);
  - a channel the page does not offer is refused (`ChannelNotOffered`);
  - so is a slot the page does not offer as free, or one another booker took meanwhile (`SlotNotFree`, « This slot was just taken »);
  - a booker who already has an upcoming appointment with the host, by the same e-mail whatever its letter case, is told when it is, on the host's clock (`AlreadyBooked`, « You already have an appointment »).

  A host's upcoming appointments are busy periods, just like their exchanges (`BusyPeriods`): a booked slot is no longer offered. The booking becomes an exchange the host planned, with one of the account's contacts or a contact to attach (`docs/architecture/contexts.md`). Its event in the host's calendar invites the booker, and Google tells them of each change (Connectors, ADR 0175).
- An appointment lives as long as the exchange it became (ADR 0171). When the exchange is erased, by the host or with its organization, the appointment is forgotten (`ForgetAppointmentOfErasedExchange`): its slot is free again and its booker has no upcoming appointment. This is a system reaction: nothing is recorded. A cancelled exchange keeps its slot taken until the slot ends.

## What it publishes

`SlotBooked` (the appointment, the account, the host, the booker's identity) and the `ConsultAppointmentsService` service (what was booked, and by whom). PersonalData and Prospecting read them to identify the booker and plan the exchange (ADR 0164). Its other domain events (`WindowsSet`, `InactivePeriodAdded`, `InactivePeriodRemoved`, `ChannelsOffered`, `BookingOpened`, `BookingClosed`, `BookingLinkChanged`) stay inside the context.

It reads:

- a member's account and its corporate name from Accounts, and how the member presents themselves from PersonalData (`Hosts`, `ConsultMembersService`, `PresentMembersService`);
- whether their calendar is connected from Connectors (`ConnectedCalendars`, `WhichHaveAConnectedCalendarService`);
- the exchanges they still have planned from Prospecting (`HostAgendas`, `ConsultPlannedExchangesService`), asked from 8 hours before the viewing, the longest exchange Prospecting plans.

It forgets an appointment on its exchange's terminal fact (`ExchangeFactsChanged`, ADR 0171).

## Features

`regler-mes-plages`, `ajouter-une-periode-inactive`, `proposer-des-canaux`, `activer-et-couper-ma-prise-de-rendez-vous`, `changer-de-lien`, `consulter-ma-prise-de-rendez-vous`, `consulter-une-page-de-prise-de-rendez-vous`, `reserver-un-creneau`, `liberer-le-rendez-vous-d-un-echange-efface`.

## In the code

- `src/Booking/Domain`:
  - `BookingPage`, `Window`, `WeeklyWindows`, `Weekday`, `WallClockTime`, `HostTimeZone`, `InactivePeriod`, `InactivePeriodId`, `Channel`, `OfferedChannels`, `BookingCode`, `BookingPageIntent`, `Availability`, `BusyPeriod`, `BookableDay`, `HostIdentity`, `Booker`, `Appointment`, `AppointmentId`; `AppointmentRequest`, `HostAgenda`;
  - the services `BusyPeriods` and `LinkAvailability`;
  - the policies `BookingPagePossibilities`, `FreeSlots`, `BookingSuspension`;
  - the repositories `BookingPageRepository` and `AppointmentRepository`;
  - the ports `Hosts`, `ConnectedCalendars` and `HostAgendas`, implemented by the API.
- `src/Booking/Application`:
  - `SetWeeklyWindows`, `AddInactivePeriod`, `RemoveInactivePeriod`, `OfferChannels`, `OpenBooking`, `CloseBooking`, `ChangeBookingLink`, through `BookingPages` (a host's page, set up by their first action on it);
  - `ConsultMyBookingPage` (`MyBookingPage`, the port `MyBookingPageRetriever`);
  - `ConsultPublicBookingPage` (`PublicBookingPage`, `PublicHost`, the port `PublicBookingPageRetriever` reading a `BookingSchedule`);
  - `BookSlot`;
  - `ConsultAppointments`, the published service;
  - `PublishSlotBooked`, its translator;
  - `ForgetAppointmentOfErasedExchange`, the reaction to an erased exchange.
