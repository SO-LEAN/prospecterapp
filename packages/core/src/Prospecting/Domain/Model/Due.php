<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\InvalidDue;

/**
 * When a to-do is due: a day of the calendar, and an hour of that day when it is said, both as the prospector
 * reads them on their clock, without a time zone.
 */
final readonly class Due
{
    private const string DAY = 'Y-m-d';
    private const string TIME = '/^([01]\d|2[0-3]):[0-5]\d$/';

    /**
     * @throws InvalidDue
     */
    public function __construct(public string $day, public ?string $time = null)
    {
        if (!self::isADay($day)) {
            throw InvalidDue::because('A to-do is due on a day', 'dueOn');
        }

        if (null !== $time && 1 !== preg_match(self::TIME, $time)) {
            throw InvalidDue::because('A to-do is due at an hour of its day', 'dueAt');
        }
    }

    /**
     * Its hour, or else its whole day, has passed at the moment, read on the clock of its reader: never converted to
     * an instant, a to-do of the 14th is on the 14th wherever it is read.
     */
    #[\NoDiscard]
    public function hasPassedBy(\DateTimeImmutable $moment): bool
    {
        return null === $this->time
            ? $this->day < $moment->format(self::DAY)
            : sprintf('%s %s', $this->day, $this->time) < $moment->format(self::DAY.' H:i');
    }

    /**
     * The days from its day to that of the moment, read on the clock of its reader: none on its day, fewer than none
     * before it.
     */
    #[\NoDiscard]
    public function daysOverdueAt(\DateTimeImmutable $moment): int
    {
        $today = self::dayAsDate($moment->format(self::DAY));
        $days = self::dayAsDate($this->day)->diff($today)->days;

        return $today < self::dayAsDate($this->day) ? -$days : $days;
    }

    private static function dayAsDate(string $day): \DateTimeImmutable
    {
        return new \DateTimeImmutable($day, new \DateTimeZone('UTC'));
    }

    private static function isADay(string $day): bool
    {
        $read = \DateTimeImmutable::createFromFormat('!'.self::DAY, $day);

        return false !== $read && $read->format(self::DAY) === $day;
    }
}
