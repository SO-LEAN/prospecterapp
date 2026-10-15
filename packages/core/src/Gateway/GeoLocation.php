<?php

declare(strict_types=1);

namespace Prospecter\Core\Gateway;

use Prospecter\Core\Entity\GeoPoint;

interface GeoLocation
{
    /**
     * The position of the address, null when it cannot be found.
     */
    public function find(string $address): ?GeoPoint;
}
