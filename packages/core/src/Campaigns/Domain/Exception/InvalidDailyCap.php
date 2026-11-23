<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/** A daily cap allows at least one send a day. */
final class InvalidDailyCap extends BusinessRuleViolation
{
    public static function belowOne(): self
    {
        return self::because('A daily cap must be at least 1 send', 'dailyCap');
    }
}
