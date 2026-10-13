<?php

declare( strict_types = 1 );

namespace Prospecter\Core\Traits\UseCase;

use Prospecter\Core\Gateway\GeoLocation;
use Prospecter\Core\Entity\GeoLocatable;
use Prospecter\Core\Entity\GeoPoint;

trait GeoLocalizeTrait
{
    /**
     * @var GeoLocation
     */
    private $geoLocation;

    private function geoLocalize(GeoLocatable $entity)
    {
        if ($entity->getAddress()) {
            $address = sprintf('%s %s %s %s', $entity->getAddress()->getStreet(), $entity->getAddress()->getPostalCode(), $entity->getAddress()->getCity(), $entity->getAddress()->getCountry());
            $response = $this->geoLocation->find($address);
            if ($response->isSucceeded()) {
                $entity->setGeoPoint(GeoPoint::fromValues($response->getLongitude(), $response->getLatitude()));
            } else {
                $entity->setGeoPoint(null);
            }
        } else {
            $entity->setGeoPoint(null);
        }
    }
}
