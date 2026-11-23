<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Company;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The text given as a SIRET is not a SIRET: it does not have 14 digits, or its check digit is wrong.
 */
final class InvalidSiret extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('SIRET "%s" is not valid', $value), 'siret', context: ['siret' => $value]);
    }
}
