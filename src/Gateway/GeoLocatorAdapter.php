<?php

namespace App\Gateway;

use App\Service\GeoLocator;
use Solean\CleanProspecter\Gateway as Solean;

class GeoLocatorAdapter implements  Solean\GeoLocation
{
    /**
     * @var GeoLocator
     */
    private $locator;

    public function __construct(GeoLocator $locator)
    {
        $this->locator = $locator;
    }

    /**
     * @param string $address
     * @return Solean\GeoLocation\GeoPointResponse
     */
    public function find(string $address): Solean\GeoLocation\GeoPointResponse
    {
        $geoPoint = $this->locator->find($address);

        return new Solean\GeoLocation\GeoPointResponse($geoPoint->getAddress(), $geoPoint->getLongitude(), $geoPoint->getLatitude());
    }
}
