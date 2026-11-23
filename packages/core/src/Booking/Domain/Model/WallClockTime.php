<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A time of day as the host reads it on their clock, written HH:MM, without a date or a time zone.
 */
final readonly class WallClockTime
{
    private const string FORMAT = '/^(?<hours>[01]\d|2[0-3]):(?<minutes>[0-5]\d)$/';
    private const int QUARTER_HOUR = 15;

    private function __construct(public int $minutesOfTheDay)
    {
    }

    public static function at(string $time, string $field): self
    {
        if (1 !== preg_match(self::FORMAT, $time, $parts)) {
            throw BusinessRuleViolation::because('A time must be a valid time in the HH:MM format', $field, context: ['time' => $time]);
        }

        return new self((int) $parts['hours'] * 60 + (int) $parts['minutes']);
    }

    public function isOnTheQuarterHour(): bool
    {
        return 0 === $this->minutesOfTheDay % self::QUARTER_HOUR;
    }

    public function minutesUntil(self $later): int
    {
        return $later->minutesOfTheDay - $this->minutesOfTheDay;
    }

    public function text(): string
    {
        return sprintf('%02d:%02d', intdiv($this->minutesOfTheDay, 60), $this->minutesOfTheDay % 60);
    }
}
