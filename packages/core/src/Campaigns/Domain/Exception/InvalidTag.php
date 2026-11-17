<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * What was typed makes no tag: nothing but separators, or more than 30 characters once written.
 */
final class InvalidTag extends BusinessRuleViolation
{
    public static function written(string $typed): self
    {
        return self::because('A tag holds 1 to 30 capital letters, digits or underscores', 'tags', context: ['tag' => $typed]);
    }
}
