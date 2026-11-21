<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The title given, how one addresses a contact, is none of Ms, Mr and x (unknown).
 */
final class InvalidTitle extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('Title "%s" is not valid', $value), 'title', context: ['title' => $value]);
    }
}
