<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The civility given is neither Madame nor Monsieur.
 */
final class InvalidCivility extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('Civility "%s" is not valid', $value), 'civility', context: ['civility' => $value]);
    }
}
