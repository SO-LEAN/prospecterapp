<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

use Prospecter\Core\Prospecting\Domain\Organization;
use Prospecter\Core\SharedKernel\Application\FileResponse;

/**
 * An organization in a list: enough to recognize it and to place it on a map.
 */
final readonly class FoundOrganization
{
    private function __construct(
        public string $id,
        public string $fullName,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
        public ?FileResponse $logo,
        public ?float $longitude,
        public ?float $latitude,
    ) {
    }

    public static function of(Organization $organization): self
    {
        $address = $organization->address();
        $geoPoint = $organization->geoPoint();

        return new self(
            id: $organization->id()->value,
            fullName: $organization->fullName(),
            postalCode: $address?->postalCode,
            city: $address?->city,
            country: $address?->country,
            logo: FileResponse::orNone($organization->logo()),
            longitude: $geoPoint?->longitude,
            latitude: $geoPoint?->latitude,
        );
    }
}
