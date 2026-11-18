<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * The text given as a SIRET is no SIRET: not 14 digits, or a wrong key.
 */
final class InvalidSiret extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('SIRET "%s" is not valid', $value), 'siret', context: ['siret' => $value]);
    }
}
