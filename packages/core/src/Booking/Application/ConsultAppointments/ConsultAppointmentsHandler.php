<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ConsultAppointments;

use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\AppointmentResponse;
use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\ConsultAppointments;
use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\ConsultAppointmentsOutput;
use Prospecter\Core\Booking\Contract\Service\ConsultAppointments\ConsultAppointmentsService;
use Prospecter\Core\Booking\Domain\Model\Appointment;
use Prospecter\Core\Booking\Domain\Model\AppointmentId;
use Prospecter\Core\Booking\Domain\Repository\AppointmentRepository;
use Prospecter\Core\SharedKernel\Application\Handler\QueryHandler;

final readonly class ConsultAppointmentsHandler implements QueryHandler, ConsultAppointmentsService
{
    public function __construct(private AppointmentRepository $appointments)
    {
    }

    public function __invoke(ConsultAppointments $query, ConsultAppointmentsOutput $output): void
    {
        $found = array_filter(array_map(fn (string $id): ?Appointment => $this->appointments->find(new AppointmentId($id)), $query->appointmentIds));

        $output->present(array_values(array_map(self::responseOf(...), $found)));
    }

    private static function responseOf(Appointment $appointment): AppointmentResponse
    {
        $booker = $appointment->booker();

        return new AppointmentResponse(
            $appointment->id()->value,
            $appointment->host()->value,
            $appointment->slot(),
            Appointment::MINUTES,
            $appointment->channel()->value,
            $booker->firstName,
            $booker->lastName,
            $booker->phone->value,
            $booker->email->value,
            $appointment->subject(),
        );
    }
}
