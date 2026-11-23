<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\Policy;

use Prospecter\Core\Booking\Application\Appointment\ForgetAppointmentOfErasedExchange\ForgetAppointmentOfErasedExchange;
use Prospecter\Core\Prospecting\Contract\Event\ExchangeFactsChanged;
use Prospecter\Core\SharedKernel\Application\Handler\Policy;

/**
 * When an exchange is erased, the appointment it was booked as is forgotten (ADR 0171).
 */
final readonly class ForgetAppointmentOfErasedExchangePolicy implements Policy
{
    public function __invoke(ExchangeFactsChanged $event): ?ForgetAppointmentOfErasedExchange
    {
        return $event->present ? null : new ForgetAppointmentOfErasedExchange($event->exchangeId, $event->occurredAt);
    }
}
