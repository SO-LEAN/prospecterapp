<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Application\ChangeBookingLink;

final readonly class ChangeBookingLink
{
    /**
     * @param string $drawnCode 22 random characters of base 62 the API drew, the new code of the link
     */
    public function __construct(
        public string $memberId,
        public string $drawnCode,
        public \DateTimeImmutable $executedAt,
    ) {
    }
}
