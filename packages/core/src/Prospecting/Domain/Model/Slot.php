<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * A moment in a prospector's agenda and how long it lasts. A planned exchange takes one.
 */
final readonly class Slot
{
    public function __construct(public \DateTimeImmutable $start, public Duration $duration)
    {
    }

    #[\NoDiscard]
    public function end(): \DateTimeImmutable
    {
        return $this->duration->endOf($this->start);
    }

    #[\NoDiscard]
    public function hasEndedBy(\DateTimeImmutable $moment): bool
    {
        return $this->end() <= $moment;
    }

    /**
     * A slot that ends when the other starts does not overlap it.
     */
    #[\NoDiscard]
    public function overlaps(self $other): bool
    {
        return $this->start < $other->end() && $other->start < $this->end();
    }

    /**
     * A slot lasts at most the longest duration. So no slot that starts before the returned moment overlaps this one.
     */
    #[\NoDiscard]
    public function earliestStartOfAnOverlap(): \DateTimeImmutable
    {
        return $this->start->modify(sprintf('-%d minutes', Duration::LONGEST));
    }
}
