<?php

namespace App\Api\Resource;

final readonly class OrganizationSummaryResource
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
        public ?LocationResource $location,
        public ?ImageResource $logo,
    ) {
    }
}
