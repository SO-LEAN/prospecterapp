<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/** The typed text makes no tag. It holds only separators, or it has more than 30 characters once written as a tag. */
final class InvalidTag extends BusinessRuleViolation
{
    public static function written(string $typed): self
    {
        return self::because('A tag holds 1 to 30 capital letters, digits or underscores', 'tags', context: ['tag' => $typed]);
    }
}
