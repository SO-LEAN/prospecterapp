<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\DurationOutOfRange;

/**
 * How long a planned exchange lasts, in minutes.
 */
final readonly class Duration
{
    public const int SHORTEST = 5;
    public const int LONGEST = 8 * 60;
    private const int USUAL = 30;

    /**
     * @throws DurationOutOfRange
     */
    private function __construct(public int $minutes)
    {
        if ($minutes < self::SHORTEST || $minutes > self::LONGEST) {
            throw DurationOutOfRange::because('A planned exchange lasts from 5 minutes to 8 hours', 'duration');
        }
    }

    /**
     * Half an hour when not said.
     *
     * @throws DurationOutOfRange
     */
    public static function inMinutes(?int $minutes): self
    {
        return new self($minutes ?? self::USUAL);
    }

    #[\NoDiscard]
    public function endOf(\DateTimeImmutable $start): \DateTimeImmutable
    {
        return $start->modify(sprintf('+%d minutes', $this->minutes));
    }
}
