<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * A day a booker can choose, on the host's clock, with the start times of its free slots.
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
