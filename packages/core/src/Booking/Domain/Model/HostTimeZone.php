<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Exception\UnknownTimeZone;

/**
 * The time zone of the clock the host sets their windows on, by its IANA name: the slots are computed in it.
 */
final readonly class HostTimeZone
{
    public function __construct(public string $name)
    {
        if (!in_array($name, \DateTimeZone::listIdentifiers(), true)) {
            throw UnknownTimeZone::named($name);
        }
    }
}
