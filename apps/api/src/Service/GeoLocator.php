<?php

namespace App\Service;

use App\Service\GeoLocator\GeoPointResponse;

interface GeoLocator
{
    public function find(string $address): GeoPointResponse;
}
