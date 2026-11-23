<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * A time when the host is already busy with an exchange they planned. It starts at the exchange's start and lasts its
 * minutes.
 */
final readonly class BusyPeriod
{
    public function __construct(
        public \DateTimeImmutable $start,
        public int $minutes,
    ) {
    }

    /**
     * Both periods are half-open: a period that ends when another starts does not overlap it.
     */
    public function overlaps(\DateTimeImmutable $from, \DateTimeImmutable $until): bool
    {
        return $this->start < $until && $from < $this->start->modify(sprintf('+%d minutes', $this->minutes));
    }
}
