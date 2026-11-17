<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\Campaigns\Domain\Model\Tag;
use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Prospecter sets this tag by itself: a member never puts it by hand (ADR 0138).
 */
final class AutomaticTag extends BusinessRuleViolation
{
    public static function named(Tag $tag): self
    {
        return self::because(sprintf('The tag %s is set automatically', $tag), 'tags', context: ['tag' => (string) $tag]);
    }
}
