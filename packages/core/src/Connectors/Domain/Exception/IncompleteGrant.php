<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * On the consent screen, the member unticked the calendar access. A connection needs that access.
 */
final class IncompleteGrant extends BusinessRuleViolation
{
    public static function ofTheCalendar(): self
    {
        return self::because('The access to the calendar was not granted');
    }
}
