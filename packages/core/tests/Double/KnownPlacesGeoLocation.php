<?php

declare(strict_types=1);

namespace Tests\Core\Double;

use Prospecter\Core\SharedKernel\Application\Port\GeoLocation;
use Prospecter\Core\SharedKernel\Domain\GeoPoint;
use Prospecter\Core\SharedKernel\Domain\Location;

/**
 * A geocoder that knows the places the scenario declares. It finds an address that mentions one of them at the
 * place's position, in its municipality. It finds no other address.
 */
final class KnownPlacesGeoLocation implements GeoLocation
{
    /** The municipality of a place declared without one: a code in no department, so it gives no geographic tag. */
    public const string NO_MUNICIPALITY = '00000';

    /** @var array<string, Location> */
    private array $places = [];

    public function know(string $place, float $longitude, float $latitude, string $municipalityCode = self::NO_MUNICIPALITY): void
    {
        $this->places[mb_strtolower($place)] = new Location(new GeoPoint($longitude, $latitude), $municipalityCode);
    }

    public function find(string $address): ?Location
    {
        foreach ($this->places as $place => $location) {
            if (str_contains(mb_strtolower($address), $place)) {
                return $location;
            }
        }

        return null;
    }
}
