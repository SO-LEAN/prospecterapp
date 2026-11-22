<?php

declare(strict_types=1);

namespace App\Prospecting\Resource;

use App\Shared\Resource\ImageResource;
use App\Shared\Resource\LocationResource;

final readonly class OrganizationSummaryResource
{
    /**
     * @param list<PossibleDuplicateResource> $possibleDuplicates the organizations of the account it resembles
     */
    public function __construct(
        public string $id,
        public string $name,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
        public ?LocationResource $location,
        public ?ImageResource $logo,
        public array $possibleDuplicates,
    ) {
    }
}
