<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The code of a public link is 22 random characters of base 62: a client drawing anything else is refused.
 */
final class InvalidBookingCode extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('A booking code is 22 random characters of base 62', 'code');
    }
}
