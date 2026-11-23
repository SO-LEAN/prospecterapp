<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain\Location;

/**
 * Where the geocoder places an address: its position and its municipality's INSEE code (ADR 0142). In Paris, Lyon
 * and Marseille, the geocoder gives the arrondissement's code.
 */
final readonly class Location
{
    public function __construct(
        public GeoPoint $point,
        public string $municipalityCode,
    ) {
    }
}
