<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application;

use Prospecter\Core\Prospecting\Application\Port\GeoLocation;
use Prospecter\Core\Prospecting\Domain\Address;
use Prospecter\Core\Prospecting\Domain\GeoPoint;

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
