<?php

namespace App\Service\GeoLocator;

class GeoPointResponse
{
    public function __construct(
        private readonly string $address,
        private readonly float $longitude,
        private readonly float $latitude,
        private readonly bool $succeeded = true,
    ) {
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getLongitude(): float
    {
        return $this->longitude;
    }

    public function getLatitude(): float
    {
        return $this->latitude;
    }

    public function isSucceeded(): bool
    {
        return $this->succeeded;
    }
}
