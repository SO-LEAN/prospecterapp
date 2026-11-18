<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\RemoveInactivePeriod;

final readonly class RemoveInactivePeriod
{
    public function __construct(
        public string $memberId,
        public string $periodId,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
