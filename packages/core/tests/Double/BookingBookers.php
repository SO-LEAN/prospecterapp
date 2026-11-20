<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\ConsultAppointments;
use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\ConsultAppointmentsService;
use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\KeptAppointments;
use Prospecter\Core\PersonalData\Domain\Model\Booker;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Port\Bookers;
use Prospecter\Core\SharedKernel\Domain\Email;
use Prospecter\Core\SharedKernel\Domain\PhoneNumber;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089): PersonalData knows who booked an appointment
 * from Booking, asked about a set of one.
 */
final readonly class BookingBookers implements Bookers
{
    public function __construct(private ConsultAppointmentsService $consultAppointmentsService)
    {
    }

    public function bookerOf(string $appointmentId): ?Booker
    {
        $kept = new KeptAppointments();
        ($this->consultAppointmentsService)(new ConsultAppointments([$appointmentId]), $kept);
        $appointment = $kept->appointments[0] ?? null;

        return null === $appointment ? null : new Booker(new PersonName($appointment->firstName, $appointment->lastName), PhoneNumber::of($appointment->phone), new Email($appointment->email));
    }
}
