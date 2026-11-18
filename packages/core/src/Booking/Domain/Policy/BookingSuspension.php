<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Policy;

/**
 * The calendar of the host invites the booker: an open page whose calendar is no longer connected is suspended,
 * offering nothing, until it is connected again (ADR 0153).
 */
final readonly class BookingSuspension
{
    #[\NoDiscard]
    public static function isSuspended(bool $open, bool $calendarConnected): bool
    {
        return $open && !$calendarConnected;
    }
}
