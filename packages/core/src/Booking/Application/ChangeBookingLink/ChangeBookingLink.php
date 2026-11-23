<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ChangeBookingLink;

final readonly class ChangeBookingLink
{
    /**
     * @param string $drawnCode the link's new code: 22 random base-62 characters that the API drew
     */
    public function __construct(
        public string $memberId,
        public string $drawnCode,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
