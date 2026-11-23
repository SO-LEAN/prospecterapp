<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * Nobody can book through a link that leads to no page, or to a closed or suspended page. The refusal does not say why,
 * because the link offers nothing (ADR 0153).
 */
final class BookingUnavailable extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('This booking link offers no slot');
    }
}
