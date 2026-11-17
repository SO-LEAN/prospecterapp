<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Port;

use Prospecter\Core\SharedKernel\Domain\Location;

interface GeoLocation
{
    /**
     * Where the address is, null when it cannot be found.
     */
    public function find(string $address): ?Location;
}
