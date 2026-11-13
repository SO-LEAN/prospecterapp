<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

/**
 * A moment of the agenda of a prospector and how long it lasts: what a planned exchange takes.
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

    /**
     * One ending when the other starts does not overlap it.
     */
    #[\NoDiscard]
    public function overlaps(self $other): bool
    {
        return $this->start < $other->end() && $other->start < $this->end();
    }

    /**
     * No slot starting earlier overlaps this one, lasting at most the longest duration.
     */
    #[\NoDiscard]
    public function earliestStartOfAnOverlap(): \DateTimeImmutable
    {
        return $this->start->modify(sprintf('-%d minutes', Duration::LONGEST));
    }
}
