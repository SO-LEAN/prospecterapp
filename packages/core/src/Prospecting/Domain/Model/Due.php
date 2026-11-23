<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Domain\Model;

use Prospecter\Core\Prospecting\Domain\Exception\InvalidDue;

/**
 * When a to-do is due: a calendar day, and an hour of that day when the prospector gives one. The prospector reads
 * both on their clock, without a time zone.
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
            throw InvalidDue::because('A to-do must be due on a valid day', 'dueOn');
        }

        if (null !== $time && 1 !== preg_match(self::TIME, $time)) {
            throw InvalidDue::because('A to-do must be due at a valid hour of its day', 'dueAt');
        }
    }

    /**
     * Tells whether its hour, or else its whole day, has passed at the moment, read on the reader's clock. It is never
     * converted to an instant: a to-do due on the 14th is on the 14th wherever it is read.
     */
    #[\NoDiscard]
    public function hasPassedBy(\DateTimeImmutable $moment): bool
    {
        return null === $this->time
            ? $this->day < $moment->format(self::DAY)
            : sprintf('%s %s', $this->day, $this->time) < $moment->format(self::DAY.' H:i');
    }

    /**
     * The number of days from its day to the moment's day, read on the reader's clock. It is zero on its day, and
     * negative before it.
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
