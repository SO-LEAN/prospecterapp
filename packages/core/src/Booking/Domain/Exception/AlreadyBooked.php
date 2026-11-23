<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A booker has at most one upcoming appointment with a host, matched by e-mail. For another one, the booker asks the
 * host by e-mail or by phone. The refusal gives the booked appointment's time on the host's clock.
 */
final class AlreadyBooked extends BusinessRuleViolation
{
    public static function at(\DateTimeImmutable $slot): self
    {
        return self::because('You already have an appointment', 'email', context: ['appointment' => $slot->format(\DATE_ATOM)]);
    }
}
