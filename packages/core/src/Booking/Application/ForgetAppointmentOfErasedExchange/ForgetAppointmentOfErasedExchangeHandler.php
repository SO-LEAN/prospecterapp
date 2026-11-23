<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ForgetAppointmentOfErasedExchange;

use Prospecter\Core\Booking\Domain\Model\AppointmentId;
use Prospecter\Core\Booking\Domain\Repository\AppointmentRepository;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Application\EventHandler;

/**
 * An appointment lives as long as the exchange it became, which has the same id (ADR 0171). A member may erase the
 * exchange, or the exchange may go with its erased organization. Then the appointment's slot is free again, and Booking
 * forgets the booker. An exchange that no booking became, or an event delivered twice, leaves nothing to do.
 */
final readonly class ForgetAppointmentOfErasedExchangeHandler implements EventHandler
{
    public function __construct(private AppointmentRepository $appointments)
    {
    }

    public function __invoke(ExchangeFactsChanged $event): void
    {
        if ($event->present) {
            return;
        }

        $appointment = $this->appointments->find(new AppointmentId($event->exchangeId));

        if (null !== $appointment) {
            $this->appointments->remove($appointment);
        }
    }
}
