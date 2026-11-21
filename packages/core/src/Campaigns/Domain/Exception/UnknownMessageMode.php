<?php

declare(strict_types=1);

namespace Prospecter\Core\Campaigns\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * An AI sends the message of a campaign as it is rendered, or adapted to each person, nothing else.
 */
final class UnknownMessageMode extends BusinessRuleViolation
{
    public static function named(string $name): self
    {
        return self::because('An AI sends the message as is or adapted', 'messageMode', context: ['messageMode' => $name]);
    }
}
