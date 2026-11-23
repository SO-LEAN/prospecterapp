<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\BusinessRuleViolation;

/**
 * A time zone is an IANA time zone, given by its name (Europe/Paris).
 */
final class UnknownTimeZone extends BusinessRuleViolation
{
    public static function named(string $name): self
    {
        return self::because(sprintf('Unknown time zone %s', $name), 'timeZone', context: ['timeZone' => $name]);
    }
}
