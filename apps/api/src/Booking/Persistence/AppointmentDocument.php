<?php

declare(strict_types=1);

namespace App\Booking\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
use App\Shared\Typed;
use Prospecter\Core\Booking\Domain\Model\Appointment;
use Prospecter\Core\Booking\Domain\Model\AppointmentId;
use Prospecter\Core\Booking\Domain\Model\Booker;
use Prospecter\Core\Booking\Domain\Model\Channel;
use Prospecter\Core\SharedKernel\Domain\AccountId;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\MemberId;
use Prospecter\Core\SharedKernel\Domain\PersonId;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * An appointment as its items keep it (ADR 0048, 0164): its slot as a moment, with the host's time zone at booking
 * time, and the booker as they described themselves.
 */
final class AppointmentDocument
{
    /**
     * @return array<string, mixed>
     */
    public static function of(Appointment $appointment): array
    {
        $state = Hydrator::stateOf($appointment);
        $booker = $appointment->booker();

        return [
            'id' => $appointment->id()->value,
            'accountId' => $appointment->accountId()->value,
            'host' => $appointment->host()->value,
            'bookerId' => $appointment->bookerId()->value,
            'slot' => Documents::moment($appointment->slot()),
            'timeZone' => $appointment->slot()->getTimezone()->getName(),
            'channel' => $appointment->channel()->value,
            'firstName' => $booker->firstName,
            'lastName' => $booker->lastName,
            'phone' => $booker->phone->value,
            'email' => $booker->email->value,
            'subject' => $appointment->subject(),
            'createdAt' => Documents::moment(Typed::object($state['createdAt'], \DateTimeImmutable::class)),
        ];
    }

    /**
     * Restores the appointment from its item's untyped document.
     */
    public static function appointment(mixed $document): Appointment
    {
        $document = Typed::document($document);

        return Hydrator::restore(Appointment::class, [
            'id' => new AppointmentId(Typed::string($document['id'])),
            'accountId' => new AccountId(Typed::string($document['accountId'])),
            'host' => new MemberId(Typed::string($document['host'])),
            'bookerId' => new PersonId(Typed::string($document['bookerId'])),
            'slot' => Documents::momentOf(Typed::string($document['slot']))->setTimezone(new \DateTimeZone(Typed::string($document['timeZone']))),
            'channel' => Channel::from(Typed::string($document['channel'])),
            'booker' => Hydrator::restore(Booker::class, [
                'firstName' => Typed::string($document['firstName']),
                'lastName' => Typed::string($document['lastName']),
                'phone' => PhoneNumber::of(Typed::string($document['phone'])),
                'email' => new Email(Typed::string($document['email'])),
            ]),
            'subject' => Typed::stringOrNull($document['subject']),
            'createdAt' => Documents::momentOf(Typed::string($document['createdAt'])),
        ]);
    }
}
