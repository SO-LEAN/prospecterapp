# 0171. An appointment booked by a public link is forgotten once the exchange it became is erased

- Date: 2026-11-20
- Type: architecture
- Status: Accepted
- Supersedes: for « an appointment stays booked whatever becomes of its exchange » (its Consequences), [0164](0164-a-slot-booked-by-a-public-link-becomes-a-planned-exchange-of-its-host-its-booker-identified-by-personaldata.md): the exchange erased, the appointment is forgotten
- Implemented by: `packages/core/src/Booking/Application/ForgetAppointmentOfErasedExchange`, `packages/core/src/Booking/Domain/Repository/AppointmentRepository.php` (`remove()`), `packages/core/features/liberer-le-rendez-vous-d-un-echange-efface.feature`, `apps/api/src/Booking/Persistence/DynamoAppointmentRepository.php`, `apps/api/config/subscriptions.json`

## Context

A booking by a public link is an appointment of Booking, holding its slot and the e-mail of its booker until it ends, and an exchange of Prospecting planned under its identity (ADR 0164). Stopping to prospect the organization of the booker erases the organization, its contacts and their exchanges, as a member erasing the exchange erases it: the appointment stayed. Its slot was still taken with no meeting behind it, the booker was told of an appointment that no longer existed, and Booking kept the e-mail of a person erased everywhere else (#179, refused at QA).

## Decision

- An appointment lives as long as the exchange it became. Booking reacts to the terminal fact of an exchange (`ExchangeFactsChanged`, absent), whether a member erased it or it went with its organization: the appointment of the same identity is forgotten, its two items removed together (`AppointmentRepository::remove`), the slot free again and the e-mail of the booker with an appointment to come no more (`ForgetAppointmentOfErasedExchange`, the subscription `booking-forget-appointment-of-erased-exchange`, its `payload` the facts of an exchange absent, without inbox: an appointment forgotten again is not there).
- An exchange no booking became has no appointment of its identity: nothing to do.

## Consequences

- An exchange cancelled, not erased, still leaves its slot taken and the e-mail with an appointment to come until it ends, as ADR 0164 says.
- A contact erased while the exchange of their booking stays, named « Contact effacé », leaves the appointment, and so the e-mail of its booker, in Booking until the table forgets it, 16 days after the booking at most (ADR 0164).
- A contact to attach erased 30 days after their last meeting (ADR 0146) has no appointment left: the table forgot it 16 days after the booking.
- Booking receives every terminal fact of an exchange, most of them of no booking, each one read of its table.

## Alternatives considered

None: the facts of the exchanges are the only thing that says an exchange was erased, by whatever gesture.

## Revisit when

A booker cancels or moves their appointment by the link, or a host wants the slot of an exchange they cancelled offered again.
