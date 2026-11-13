<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\CalendarPeriodReversed;
use Prospecter\Core\Prospecting\Domain\Exception\CalendarPeriodTooLong;

/**
 * The days of a calendar read at once: from the start of the first, until the start of the day after the last.
 * Its length is measured on the clock of its reader, each bound at its own offset: a change of time in the period
 * neither lengthens nor shortens it.
 */
final readonly class CalendarPeriod
{
    public const int LONGEST_IN_DAYS = 6 * 7;
    private const int SECONDS_IN_A_DAY = 24 * 60 * 60;

    /**
     * @throws CalendarPeriodReversed
     * @throws CalendarPeriodTooLong
     */
    public function __construct(public \DateTimeImmutable $from, public \DateTimeImmutable $until)
    {
        if ($until <= $from) {
            throw CalendarPeriodReversed::because('A calendar period ends after it starts', 'to');
        }

        if (self::onTheClockOfItsReader($until) - self::onTheClockOfItsReader($from) > self::LONGEST_IN_DAYS * self::SECONDS_IN_A_DAY) {
            throw CalendarPeriodTooLong::because('A calendar period lasts six weeks at most', 'to');
        }
    }

    private static function onTheClockOfItsReader(\DateTimeImmutable $moment): int
    {
        return $moment->getTimestamp() + $moment->getOffset();
    }
}
