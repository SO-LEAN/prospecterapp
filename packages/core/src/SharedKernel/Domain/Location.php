<?php

declare(strict_types=1);

namespace Prospecter\Core\SharedKernel\Domain;

/**
 * Where the geocoder finds an address: its position, and the INSEE code of its municipality (ADR 0142), that of an
 * arrondissement of Paris, Lyon or Marseille as the geocoder gives it.
 */
final readonly class Location
{
    public function __construct(
        public GeoPoint $point,
        public string $municipalityCode,
    ) {
    }
}
