<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Contact;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The text given as an email is not an email.
 */
final class InvalidEmail extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('E-mail address "%s" is not valid', $value), 'email', context: ['email' => $value]);
    }
}
