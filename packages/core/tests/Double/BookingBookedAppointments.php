<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\ConsultAppointments;
use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\ConsultAppointmentsService;
use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\KeptAppointments;
use Prospecter\Core\Prospecting\Domain\Model\BookedAppointment;
use Prospecter\Core\Prospecting\Domain\Model\Channel;
use Prospecter\Core\Prospecting\Domain\Model\Duration;
use Prospecter\Core\Prospecting\Domain\Port\BookedAppointments;

/**
 * Anticorruption layer of the API, in the scenarios (ADR 0045, 0089): Prospecting knows what a booker booked from
 * Booking, asked about a set of one.
 */
final readonly class BookingBookedAppointments implements BookedAppointments
{
    public function __construct(private ConsultAppointmentsService $consultAppointmentsService)
    {
    }

    public function bookedAppointment(string $appointmentId): ?BookedAppointment
    {
        $kept = new KeptAppointments();
        ($this->consultAppointmentsService)(new ConsultAppointments([$appointmentId]), $kept);
        $appointment = $kept->appointments[0] ?? null;

        return null === $appointment ? null : new BookedAppointment($appointment->startsAt, Duration::inMinutes($appointment->durationInMinutes), Channel::from($appointment->channel), $appointment->subject);
    }
}
