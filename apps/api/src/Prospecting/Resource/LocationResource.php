<?php

namespace App\Prospecting\Resource;

use Prospecter\Core\Prospecting\Domain\GeoPoint;

final readonly class LocationResource
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
    }

    public static function from(?GeoPoint $geoPoint): ?self
    {
        return null === $geoPoint ? null : new self($geoPoint->latitude, $geoPoint->longitude);
    }
}
