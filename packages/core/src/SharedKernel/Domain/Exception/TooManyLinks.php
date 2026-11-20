<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Exception;

/**
 * A person or an organization has a few links at most: each costs a write when they are forgotten.
 */
final class TooManyLinks extends BusinessRuleViolation
{
    public static function beyond(int $max): self
    {
        return self::because(sprintf('%d links at most', $max), 'links', context: ['max' => (string) $max]);
    }
}
