<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Port;

use Prospecter\Core\Domain\Organization\GeoPoint;

interface GeoLocation
{
    /**
     * The position of the address, null when it cannot be found.
     */
    public function find(string $address): ?GeoPoint;
}
