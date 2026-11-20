# 0164. A slot booked by a public link becomes a planned exchange of its host, its booker identified by PersonalData

- Date: 2026-11-20
- Type: architecture
- Status: Accepted
- Supersedes: for « no other context keeps personal data », [0031](0031-personal-data-context.md): Booking keeps who booked an appointment, as they said it, 16 days at most; for « one aggregate per use case », [0042](0042-contacts-never-alone-domain-events.md): the reaction planning a booked appointment writes the contact to attach it meets and the exchange in one unit
- Implemented by: `packages/core/src/Booking/{Application/BookSlot,Application/ConsultAppointments,Application/PublishSlotBooked,Contract,Domain/Model/Appointment.php,Domain/Service/BusyPeriods.php}`, `packages/core/src/PersonalData/Application/{IdentifyBooker,PublishBookerIdentified}`, `packages/core/src/Prospecting/Application/PlanBookedAppointment`, `packages/core/features/reserver-un-creneau.feature`, `apps/api/src/Booking/{Controller/BookSlotController.php,Persistence/DynamoAppointmentRepository.php,Robots.php}`, `apps/api/config/subscriptions.json`, `apps/api/infra/http-api.tf`, `apps/front/src/features/booking`, `apps/front/src/features/exchanges/bookings`

## Context

A booker sees the free slots of a host by their public link (ADR 0153). They now book one, saying their name, their phone and their e-mail, without signing in (#179). The booking must become an exchange the host planned, in their calendar, with a contact the account knows, or a contact to attach (ADR 0146). Three contexts take part: Booking keeps the page, PersonalData the persons, Prospecting the exchanges. Two bookers may choose the same slot at the same moment; a page may be scanned by robots.

## Decision

- **The appointment** is an aggregate of Booking (`Appointment`), booked by the page of its host (`BookingPage::book`, an `AppointmentRequest` against the `HostAgenda`): the page open with its calendar connected and its host allowed to manage personal data, who holds the contacts their bookers become (`LinkAvailability`, ADR 0146: the link of any other host offers nothing and books nothing), a channel it offers, a slot among the free ones it offers (`FreeSlots`), no other appointment of the e-mail to come with the host (`AlreadyBooked`, its moment on the clock of the host in its context), the first name, the name, the phone and the e-mail given (`IncompleteBooker`). It keeps who booked it, as they said it, and the identity the booker gets should nobody of the account be them, both given by the client.
- **The slot held**: an appointment is two items of the table of Booking, `APPOINTMENT#<id>` and `SLOTS#<host>` / `SLOT#<start>`, the second put under `attribute_not_exists(PK)`: two bookings of one slot never both pass (`SlotNotFree`, « This slot was just taken »). Both are forgotten by the table 16 days after the booking (`expiresAt`), past the last slot a link offers. The free slots leave out the appointments to come of the host as they leave out their exchanges (`BusyPeriods`), before Prospecting plans them.
- **No personal data travels in an event**: Booking publishes `SlotBooked` (the appointment, the account, the host, the identity of the booker) and answers what was booked and by whom through its published `ConsultAppointmentsService`. PersonalData reacts (`IdentifyBooker`, the subscription `personal-data-identify-booker`): the contact of the account having the e-mail, otherwise one having the phone, otherwise a contact to attach it collects for the host (`Person::collectToAttach`); it publishes `BookerIdentified` (the appointment, the host, the person). Prospecting reacts (`PlanBookedAppointment`, `prospecting-plan-booked-appointment`): a person no organization names and no one holds to attach is met to be attached by the host, then the exchange is planned, held by the host, under the identity of the appointment, its text the subject, a video call asking its link of the calendar of the host (ADR 0137), and booked by its contact (`Exchange::bookedByAContact`), which its timeline, its calendar and its detail say. That reaction writes the contact to attach and the exchange in one unit: the meeting is what makes the person a contact to attach.
- **The route** `POST /api/booking-pages/{code}/appointments`, behind the firewall without security of the public page, is limited by the code of its link like the page (ADR 0153); on AWS its own route of the HTTP API caps its rate (`public_booking_book_rate_limit`, 2 a second, `public_booking_book_burst_limit`, 5). A form whose hidden trap (`website`) is filled, or sent less than three seconds after the page showed it (`formShownAt`), books nothing and is answered as booked (`Robots`). No captcha.
- **The host is told** of a booking at the next reading of their agenda while the application is open: the shell reads the calendar of the 16 days to come every five minutes, and a toast tells each exchange booked by the link it had not found at its first reading.

## Consequences

- An appointment stays booked whatever becomes of its exchange: an exchange the host cancels leaves the slot taken and the e-mail with an appointment to come, until the appointment ends; the booker calls or writes to the host, as for any other change.
- A host who loses the right to manage personal data while a booking travels leaves the collection of its booker refused in the failures of the worker (ADR 0146).
- Booking keeps the name, the phone and the e-mail of a booker 16 days at most; PersonalData keeps them as a contact, a contact to attach erased 30 days after their last meeting.
- The shell reads the calendar of the 16 days to come every five minutes.

## Alternatives considered

- `SlotBooked` carrying who booked and the meeting, read by both reactions: personal data in the outbox and the queues of the failures. Weighed and left for the published service of Booking.
- The exchange planned by a reaction of its own to the contact to attach met (`ContactToAttachMet`), one aggregate per unit: that event knows nothing of the appointment. Weighed at the review and left.

## Revisit when

A booker cancels or moves their appointment by the link, or a host wants the slot of an exchange they cancelled offered again.
