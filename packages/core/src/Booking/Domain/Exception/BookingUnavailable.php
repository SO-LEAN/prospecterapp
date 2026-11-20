<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A link no page has now, a page closed or suspended books nothing, without saying why, as it offers nothing (ADR 0153).
 */
final class BookingUnavailable extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('This booking link offers no slot');
    }
}
