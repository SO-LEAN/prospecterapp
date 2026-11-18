<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Policy\FreeSlots;

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
     * Both are half open: a period ending when a slot starts leaves it free.
     */
    public function overlapsSlotAt(\DateTimeImmutable $slot): bool
    {
        $end = $this->start->modify(sprintf('+%d minutes', $this->minutes));

        return $this->start < $slot->modify(sprintf('+%d minutes', FreeSlots::SLOT_MINUTES)) && $slot < $end;
    }
}
