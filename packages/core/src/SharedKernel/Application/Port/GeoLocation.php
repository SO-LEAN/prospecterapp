<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Port;

use Prospecter\Core\SharedKernel\Domain\Location\Location;

interface GeoLocation
{
    /**
     * Where the address is, or null when the geocoder cannot find it.
     */
    public function find(string $address): ?Location;
}
