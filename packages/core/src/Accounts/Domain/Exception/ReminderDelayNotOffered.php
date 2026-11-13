<?php

declare(strict_types=1);

namespace Prospecter\Core\Accounts\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A reminder comes at one of the delays offered, never another.
 */
final class ReminderDelayNotOffered extends BusinessRuleViolation
{
    public static function of(int $minutes): self
    {
        return self::because('A reminder comes 0, 5, 10, 15, 30 or 60 minutes before', 'minutesBefore', context: ['minutesBefore' => (string) $minutes]);
    }
}
