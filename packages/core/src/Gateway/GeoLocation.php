<?php

declare( strict_types = 1 );

namespace Prospecter\Core\Gateway;

use Prospecter\Core\Gateway\GeoLocation\GeoPointResponse;

interface GeoLocation
{
    public function find(string $address): GeoPointResponse;
}
