<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A public link's code has 22 random base-62 characters. The core refuses a code of any other shape from a client.
 */
final class InvalidBookingCode extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('A booking code has 22 random base-62 characters', 'code');
    }
}
