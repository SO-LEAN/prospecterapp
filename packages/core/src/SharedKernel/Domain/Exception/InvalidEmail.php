<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * The text given as an email is no email.
 */
final class InvalidEmail extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('Email "%s" is not valid', $value), 'email', context: ['email' => $value]);
    }
}
