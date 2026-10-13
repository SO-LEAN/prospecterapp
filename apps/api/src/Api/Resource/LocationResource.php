<?php

namespace App\Api\Resource;

final readonly class LocationResource
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
    }

    public static function fromCoordinates(?float $latitude, ?float $longitude): ?self
    {
        return null === $latitude || null === $longitude ? null : new self($latitude, $longitude);
    }
}
