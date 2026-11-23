<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/** A campaign runs by e-mail, call, SMS or LinkedIn, and nothing else. */
final class UnknownChannel extends BusinessRuleViolation
{
    public static function named(string $name): self
    {
        return self::because('A campaign is run by e-mail, call, SMS or LinkedIn', 'channel', context: ['channel' => $name]);
    }
}
