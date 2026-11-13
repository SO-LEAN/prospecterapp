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

    private static function isADay(string $day): bool
    {
        $read = \DateTimeImmutable::createFromFormat('!'.self::DAY, $day);

        return false !== $read && $read->format(self::DAY) === $day;
    }
}
