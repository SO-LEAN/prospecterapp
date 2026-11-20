<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ForgetAppointmentOfErasedExchange;

use Prospecter\Core\Booking\Domain\Model\AppointmentId;
use Prospecter\Core\Booking\Domain\Repository\AppointmentRepository;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Application\EventHandler;

/**
 * An appointment lives as long as the exchange it became, which bears its identity (ADR 0171): the exchange erased,
 * by a member or with its organization, its slot is free again and who booked it forgotten. An exchange no booking
 * became, or delivered again, leaves nothing to do.
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
