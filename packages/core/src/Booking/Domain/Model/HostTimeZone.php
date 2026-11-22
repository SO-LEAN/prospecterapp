<?php

declare(strict_types=1);

namespace Prospecter\Core\Booking\Domain\Model;

use Prospecter\Core\Booking\Domain\Exception\UnknownTimeZone;

/**
 * The time zone of the clock the host sets their windows on, by its IANA name, a former one included, as a browser may
 * still give it: the slots are computed in it.
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
