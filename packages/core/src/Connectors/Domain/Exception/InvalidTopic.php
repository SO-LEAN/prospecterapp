<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A topic is named in full, with its project, because the mailboxes publish to that full name (ADR 0154).
 */
final class InvalidTopic extends BusinessRuleViolation
{
    public static function inFull(): self
    {
        return self::because('A topic is named projects/<project>/topics/<topic>', 'topic');
    }
}
