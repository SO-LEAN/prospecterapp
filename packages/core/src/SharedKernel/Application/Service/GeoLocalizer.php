<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Application\Service;

use Prospecter\Core\SharedKernel\Application\Port\GeoLocation;
use Prospecter\Core\SharedKernel\Domain\Contact\Address;
use Prospecter\Core\SharedKernel\Domain\Location\Location;

final readonly class GeoLocalizer
{
    public function __construct(private GeoLocation $geoLocation)
    {
    }

    public function locate(Address $address): ?Location
    {
        return $address->isEmpty() ? null : $this->geoLocation->find((string) $address);
    }
}
