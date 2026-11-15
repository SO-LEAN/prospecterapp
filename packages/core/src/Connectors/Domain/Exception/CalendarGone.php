<?php

declare(strict_types=1);

namespace Prospecter\Core\Connectors\Domain\Exception;

use Prospecter\Core\SharedKernel\Domain\Exception\NotFound;

/**
 * The calendar « Prospecter » is no longer at the provider: its member deleted it.
 */
final class CalendarGone extends NotFound
{
    public static function identifiedBy(string $calendar): self
    {
        return new self(sprintf('Calendar %s not found', $calendar));
    }
}
