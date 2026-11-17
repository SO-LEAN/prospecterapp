<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * The message of a campaign is a message, not a document: too long, it is refused.
 */
final class MessageTooLong extends BusinessRuleViolation
{
    public static function longerThan(int $max): self
    {
        return self::because(sprintf('A message has %d characters at most', $max), 'message', context: ['max' => (string) $max]);
    }
}
