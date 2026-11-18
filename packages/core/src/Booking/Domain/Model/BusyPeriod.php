<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

/**
 * A time the host is already taken, by an exchange they planned: from its start, for its minutes.
 */
final readonly class BusyPeriod
{
    public function __construct(
        public \DateTimeImmutable $start,
        public int $minutes,
    ) {
    }

    /**
     * Both are half open: a period ending when another starts leaves it free.
     */
    public function overlaps(\DateTimeImmutable $from, \DateTimeImmutable $until): bool
    {
        return $this->start < $until && $from < $this->start->modify(sprintf('+%d minutes', $this->minutes));
    }
}
