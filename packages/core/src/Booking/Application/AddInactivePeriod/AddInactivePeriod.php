<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\AddInactivePeriod;

final readonly class AddInactivePeriod
{
    /**
     * @param string $firstDay YYYY-MM-DD, on the host's clock
     * @param string $lastDay  YYYY-MM-DD, included
     */
    public function __construct(
        public string $memberId,
        public string $periodId,
        public string $firstDay,
        public string $lastDay,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
