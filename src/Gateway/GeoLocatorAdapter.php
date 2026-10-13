<?php

namespace App\Gateway;

use App\Service\GeoLocator;
use Solean\CleanProspecter\Gateway as Solean;

class GeoLocatorAdapter implements Solean\GeoLocation
{
    public function __construct(private readonly GeoLocator $locator)
    {
    }

    public function find(string $address): Solean\GeoLocation\GeoPointResponse
    {
        $geoPoint = $this->locator->find($address);

        return new Solean\GeoLocation\GeoPointResponse($geoPoint->getAddress(), $geoPoint->getLongitude(), $geoPoint->getLatitude());
    }
}
