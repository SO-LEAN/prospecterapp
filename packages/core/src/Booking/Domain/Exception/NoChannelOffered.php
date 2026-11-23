<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * An open booking page offers bookers a call, a video call, or both.
 */
final class NoChannelOffered extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('Booking offers at least one channel', 'channels');
    }
}
