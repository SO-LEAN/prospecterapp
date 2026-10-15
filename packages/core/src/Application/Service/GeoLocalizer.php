<?php

declare(strict_types=1);

namespace Prospecter\Core\Application\Service;

use Prospecter\Core\Application\Port\GeoLocation;
use Prospecter\Core\Domain\Organization\Address;
use Prospecter\Core\Domain\Organization\GeoPoint;

final readonly class GeoLocalizer
{
    public function __construct(private GeoLocation $geoLocation)
    {
    }

    public function locate(?Address $address): ?GeoPoint
    {
        return null === $address ? null : $this->geoLocation->find((string) $address);
    }
}
