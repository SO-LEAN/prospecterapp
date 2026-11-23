<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/** A campaign's message is a message, not a document. A message that is too long is refused. */
final class MessageTooLong extends BusinessRuleViolation
{
    public static function longerThan(int $max): self
    {
        return self::because(sprintf('A message can have at most %d characters', $max), 'message', context: ['max' => (string) $max]);
    }
}
