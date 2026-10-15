<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\Prospecting\Application\Port\GeoLocation;
use Prospecter\Core\Prospecting\Domain\GeoPoint;

/**
 * A geocoder that knows the places declared by the scenario: an address mentioning one of them is found
 * at its position, any other address is not found.
 */
final class KnownPlacesGeoLocation implements GeoLocation
{
    /** @var array<string, GeoPoint> */
    private array $places = [];

    public function know(string $place, float $longitude, float $latitude): void
    {
        $this->places[mb_strtolower($place)] = new GeoPoint($longitude, $latitude);
    }

    public function find(string $address): ?GeoPoint
    {
        foreach ($this->places as $place => $geoPoint) {
            if (str_contains(mb_strtolower($address), $place)) {
                return $geoPoint;
            }
        }

        return null;
    }
}
