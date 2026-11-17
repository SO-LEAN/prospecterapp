<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The contacts are searched by 1 to 5 tags at once, never none nor more.
 */
final class InvalidTagSelection extends BusinessRuleViolation
{
    public static function ofSize(int $size, int $max): self
    {
        return self::because(sprintf('Choose from 1 to %d tags', $max), 'tags', context: ['chosen' => $size]);
    }
}
