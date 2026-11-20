<?php

namespace App\Booking\Persistence;

use App\Shared\Persistence\Documents;
use App\Shared\Persistence\Hydrator;
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
 * An appointment as its items keep it (ADR 0048, 0164): its slot a moment and the time zone of the host it was
 * booked on, who booked it as they said it.
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
            'createdAt' => Documents::moment($state['createdAt']),
        ];
    }

    /**
     * @param array<string, mixed> $document
     */
    public static function appointment(array $document): Appointment
    {
        return Hydrator::restore(Appointment::class, [
            'id' => new AppointmentId($document['id']),
            'accountId' => new AccountId($document['accountId']),
            'host' => new MemberId($document['host']),
            'bookerId' => new PersonId($document['bookerId']),
            'slot' => Documents::momentOf($document['slot'])->setTimezone(new \DateTimeZone($document['timeZone'])),
            'channel' => Channel::from($document['channel']),
            'booker' => Hydrator::restore(Booker::class, [
                'firstName' => $document['firstName'],
                'lastName' => $document['lastName'],
                'phone' => PhoneNumber::of($document['phone']),
                'email' => new Email($document['email']),
            ]),
            'subject' => $document['subject'],
            'createdAt' => Documents::momentOf($document['createdAt']),
        ]);
    }
}
