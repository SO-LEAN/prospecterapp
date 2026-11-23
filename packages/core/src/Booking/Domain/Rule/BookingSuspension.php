<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Rule;

/**
 * The host's calendar invites the booker. So when an open page's calendar is disconnected, the page is suspended and
 * offers nothing. It comes back when the calendar is connected again (ADR 0153).
 */
final readonly class BookingSuspension
{
    #[\NoDiscard]
    public static function isSuspended(bool $open, bool $calendarConnected): bool
    {
        return $open && !$calendarConnected;
    }
}
