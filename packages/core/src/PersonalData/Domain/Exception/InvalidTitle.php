<?php

declare(strict_types=1);

namespace Prospecter\Core\PersonalData\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The given title is not Ms, Mr or x (unknown).
 */
final class InvalidTitle extends BusinessRuleViolation
{
    public static function of(string $value): self
    {
        return self::because(sprintf('Title "%s" is not valid', $value), 'title', context: ['title' => $value]);
    }
}
