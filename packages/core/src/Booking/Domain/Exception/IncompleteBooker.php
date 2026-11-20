<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The host needs to know whom they meet and how to reach them; only the subject may be left out.
 */
final class IncompleteBooker extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('A booker gives their first name, last name, phone and e-mail');
    }
}
