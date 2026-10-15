<?php

namespace Tests\Core\Double;

use Prospecter\Core\Gateway\GeoLocation;
use Prospecter\Core\Gateway\GeoLocation\GeoPointResponse;

/**
 * A geocoder that knows the places declared by the scenario: an address mentioning one of them is found
 * at its position, any other address is not found.
 */
final class KnownPlacesGeoLocation implements GeoLocation
{
    /** @var array<string, array{float, float}> place => [longitude, latitude] */
    private array $places = [];

    public function know(string $place, float $longitude, float $latitude): void
    {
        $this->places[mb_strtolower($place)] = [$longitude, $latitude];
    }

    public function find(string $address): GeoPointResponse
    {
        foreach ($this->places as $place => [$longitude, $latitude]) {
            if (str_contains(mb_strtolower($address), $place)) {
                return new GeoPointResponse($address, $longitude, $latitude);
            }
        }

        return new GeoPointResponse($address, 0, 0, false);
    }
}
