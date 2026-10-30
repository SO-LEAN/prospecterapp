<?php

declare(strict_types=1);

namespace Prospecter\Core\Prospecting\Application\SearchProspectedOrganizations;

use Prospecter\Core\SharedKernel\Application\FileResponse;

/**
 * An organization in a list: enough to recognize it and to place it on a map.
 */
final readonly class FoundOrganization
{
    public function __construct(
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
}
