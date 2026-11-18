<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\OpenBooking;

final readonly class OpenBooking
{
    /**
     * @param string $drawnCode 22 random characters of base 62 the API drew, the code of the link of a page that has none yet
     */
    public function __construct(
        public string $memberId,
        public string $drawnCode,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
