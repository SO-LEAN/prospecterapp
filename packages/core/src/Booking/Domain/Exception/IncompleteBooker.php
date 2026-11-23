<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The host needs to know who they meet and how to reach them. Only the subject is optional.
 */
final class IncompleteBooker extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('A booker must give their first name, last name, phone number and e-mail address');
    }
}
