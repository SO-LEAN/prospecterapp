<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Contact;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The text given as a phone number is not a valid number.
 */
final class InvalidPhoneNumber extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('Phone number "%s" is not valid', $value), 'phoneNumber', context: ['phoneNumber' => $value]);
    }
}
