<?php

namespace App\Prospecting\Resource;

final readonly class LocationResource
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
    }

    public static function orNone(?float $latitude, ?float $longitude): ?self
    {
        return null === $latitude || null === $longitude ? null : new self($latitude, $longitude);
    }
}
