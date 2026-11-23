<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A booker books one of the free slots that the link offers. Another booker or one of the host's exchanges may take the
 * slot after the booker read the page. The booking is then refused, even when both book at the same moment (ADR 0164).
 */
final class SlotNotFree extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('This slot was just taken', 'slot');
    }
}
