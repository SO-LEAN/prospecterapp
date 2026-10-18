<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application;

use Prospecter\Core\SharedKernel\Application\Port\GeoLocation;
use Prospecter\Core\SharedKernel\Domain\Address;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;

final readonly class GeoLocalizer
{
    public function __construct(private GeoLocation $geoLocation)
    {
    }

    public function locate(Address $address): ?GeoPoint
    {
        return $address->isEmpty() ? null : $this->geoLocation->find((string) $address);
    }
}
