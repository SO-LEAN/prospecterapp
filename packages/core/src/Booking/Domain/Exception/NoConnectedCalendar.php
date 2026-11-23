<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A booking goes into the host's calendar, and the calendar invites the booker. So a page opens only while that
 * calendar is connected.
 */
final class NoConnectedCalendar extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('Booking needs a connected calendar to invite the booker');
    }
}
