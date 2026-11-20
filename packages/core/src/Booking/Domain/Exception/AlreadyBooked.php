<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * One appointment to come by e-mail with a host: another is asked of the host by e-mail or by phone. The refusal says
 * when the one booked is, on the clock of the host.
 */
final class AlreadyBooked extends BusinessRuleViolation
{
    public static function at(\DateTimeImmutable $slot): self
    {
        return self::because('You already have an appointment', 'email', context: ['appointment' => $slot->format(\DATE_ATOM)]);
    }
}
