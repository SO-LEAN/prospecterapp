<?php

declare(strict_types=1);

namespace Prospecter\Core\Service;

use Prospecter\Core\Entity\GeoLocatable;
use Prospecter\Core\Gateway\GeoLocation;

final readonly class GeoLocalizer
{
    public function __construct(private GeoLocation $geoLocation)
    {
    }

    public function locate(GeoLocatable $entity): void
    {
        $address = $entity->getAddress();

        $entity->setGeoPoint(null === $address ? null : $this->geoLocation->find((string) $address));
    }
}
