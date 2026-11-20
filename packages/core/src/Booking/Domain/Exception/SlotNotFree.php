<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A booker books one of the free slots the link offers; one taken since they read the page, by another booker or an
 * exchange of the host, is refused, even when both book at the same moment (ADR 0164).
 */
final class SlotNotFree extends BusinessRuleViolation
{
    public static function refused(): self
    {
        return self::because('This slot was just taken', 'slot');
    }
}
