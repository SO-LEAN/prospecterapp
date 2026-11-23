<?php

declare(strict_types=1);

namespace App\PersonalData\AntiCorruption;

use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\ConsultAppointments;
use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\ConsultAppointmentsService;
use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\KeptAppointments;
use Prospecter\Core\PersonalData\Domain\Model\Booker;
use Prospecter\Core\PersonalData\Domain\Model\PersonName;
use Prospecter\Core\PersonalData\Domain\Port\Bookers;
use Prospecter\Core\SharedKernel\Domain\Contact\Email;
use Prospecter\Core\SharedKernel\Domain\Contact\PhoneNumber;

/**
 * Anticorruption layer (ADR 0045, 0089). PersonalData learns from Booking who booked an appointment. It asks
 * Booking about a set of one appointment.
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
