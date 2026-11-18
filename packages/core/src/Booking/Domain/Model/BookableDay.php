<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * A day a booker may choose from, on the clock of the host, and the moments its free slots start at.
 */
final readonly class BookableDay
{
    /**
     * @param string                   $day   YYYY-MM-DD
     * @param list<\DateTimeImmutable> $slots
     */
    public function __construct(
        public string $day,
        public array $slots,
    ) {
    }
}
