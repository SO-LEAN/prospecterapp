<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A topic is named in full, its project with it, as the mailboxes publish to it (ADR 0154).
 */
final class InvalidTopic extends BusinessRuleViolation
{
    public static function named(string $topic): self
    {
        return self::because('A topic is named projects/<project>/topics/<topic>', 'topic', context: ['topic' => $topic]);
    }
}
