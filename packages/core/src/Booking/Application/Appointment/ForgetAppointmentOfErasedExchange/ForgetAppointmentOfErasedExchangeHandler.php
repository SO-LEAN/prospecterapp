<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\Appointment\ForgetAppointmentOfErasedExchange;

use Prospecter\Core\Booking\Domain\Model\AppointmentId;
use Prospecter\Core\Booking\Domain\Repository\AppointmentRepository;
use Prospecter\Core\SharedKernel\Application\Handler\CommandHandler;

/**
 * An appointment lives as long as the exchange it became, which has the same id (ADR 0171). A member may erase the
 * exchange, or the exchange may go with its erased organization. Then the appointment's slot is free again, and Booking
 * forgets the booker. An exchange that no booking became, or an erasure sent twice, leaves nothing to do.
 */
final readonly class ForgetAppointmentOfErasedExchangeHandler implements CommandHandler
{
    public function __construct(private AppointmentRepository $appointments)
    {
    }

    public function __invoke(ForgetAppointmentOfErasedExchange $command): void
    {
        $appointment = $this->appointments->find(new AppointmentId($command->exchangeId));

        if (null !== $appointment) {
            $this->appointments->remove($appointment);
        }
    }
}
