<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A booker meets the host by one of the channels the host offers.
 */
final class ChannelNotOffered extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('This channel is not offered', 'channel');
    }
}
