<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\CalendarPeriodReversed;
use Prospecter\Core\Prospecting\Domain\Exception\CalendarPeriodTooLong;

/**
 * The calendar days read at once. The period runs from the start of the first day until the start of the day after
 * the last. Its length is measured on the reader's clock, each bound at its own offset. A clock change in the period
 * neither lengthens nor shortens it.
 */
final readonly class CalendarPeriod
{
    public const int LONGEST_IN_DAYS = 6 * 7;
    private const int SECONDS_IN_A_DAY = 24 * 60 * 60;
    private const string DAY = 'Y-m-d';

    /**
     * @throws CalendarPeriodReversed
     * @throws CalendarPeriodTooLong
     */
    public function __construct(public \DateTimeImmutable $from, public \DateTimeImmutable $until)
    {
        if ($until <= $from) {
            throw CalendarPeriodReversed::because('A calendar period must end after it starts', 'to');
        }

        if (self::onTheClockOfItsReader($until) - self::onTheClockOfItsReader($from) > self::LONGEST_IN_DAYS * self::SECONDS_IN_A_DAY) {
            throw CalendarPeriodTooLong::because('A calendar period can last at most six weeks', 'to');
        }
    }

    /**
     * Its first day, `Y-m-d`, on the reader's clock.
     */
    #[\NoDiscard]
    public function firstDay(): string
    {
        return $this->from->format(self::DAY);
    }

    /**
     * Its last day, `Y-m-d`, on the reader's clock: the day before it ends.
     */
    #[\NoDiscard]
    public function lastDay(): string
    {
        return $this->until->modify('-1 second')->format(self::DAY);
    }

    private static function onTheClockOfItsReader(\DateTimeImmutable $moment): int
    {
        return $moment->getTimestamp() + $moment->getOffset();
    }
}
