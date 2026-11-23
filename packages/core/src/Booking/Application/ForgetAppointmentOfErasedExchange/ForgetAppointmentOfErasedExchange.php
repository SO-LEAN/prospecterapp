<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ForgetAppointmentOfErasedExchange;

final readonly class ForgetAppointmentOfErasedExchange
{
    public function __construct(
        public string $exchangeId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
