<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Exception\UnknownTimeZone;

/**
 * The time zone of the clock on which the host sets their windows, given by its IANA name. A deprecated name is
 * accepted, because a browser may still send it. The slots are computed in this time zone.
 */
final readonly class HostTimeZone
{
    public function __construct(public string $name)
    {
        if (!in_array($name, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL_WITH_BC), true)) {
            throw UnknownTimeZone::named($name);
        }
    }

    public function clock(): \DateTimeZone
    {
        return new \DateTimeZone($this->name);
    }
}
