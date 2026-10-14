<?php

namespace App\Gateway;

use App\Service\GeoLocator;
use Prospecter\Core\Gateway as Core;

class GeoLocatorAdapter implements Core\GeoLocation
{
    public function __construct(private readonly GeoLocator $locator)
    {
    }

    public function find(string $address): Core\GeoLocation\GeoPointResponse
    {
        $geoPoint = $this->locator->find($address);

        return new Core\GeoLocation\GeoPointResponse($geoPoint->getAddress(), $geoPoint->getLongitude(), $geoPoint->getLatitude(), $geoPoint->isSucceeded());
    }
}
