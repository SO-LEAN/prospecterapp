<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Exception\PeriodEndsBeforeItStarts;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Days when nobody can book the host, whatever their windows say. The period runs from a first day to a last day, both
 * included, written YYYY-MM-DD on the host's clock. It has no label: it marks an unavailability, not a note.
 */
final readonly class InactivePeriod
{
    private const string DAY = '!Y-m-d';

    public function __construct(
        public InactivePeriodId $id,
        public string $firstDay,
        public string $lastDay,
    ) {
        self::ensureADay($firstDay, 'firstDay');
        self::ensureADay($lastDay, 'lastDay');
        if ($lastDay < $firstDay) {
            throw PeriodEndsBeforeItStarts::refused();
        }
    }

    public function covers(string $day): bool
    {
        return $this->firstDay <= $day && $day <= $this->lastDay;
    }

    public function hasEndedBefore(string $day): bool
    {
        return $this->lastDay < $day;
    }

    private static function ensureADay(string $day, string $field): void
    {
        $read = \DateTimeImmutable::createFromFormat(self::DAY, $day);
        if (false === $read || $read->format('Y-m-d') !== $day) {
            throw BusinessRuleViolation::because('A day is written YYYY-MM-DD', $field, context: ['day' => $day]);
        }
    }
}
