<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * The text given as a phone number is no valid number.
 */
final class InvalidPhoneNumber extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('Phone number "%s" is not valid', $value), 'phoneNumber', context: ['phoneNumber' => $value]);
    }
}
