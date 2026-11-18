<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A booking is put in the calendar of the host, which invites the booker: a page opens only while that calendar is
 * connected.
 */
final class NoConnectedCalendar extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('Booking needs a connected calendar, which invites the booker');
    }
}
