<?php

namespace App\Api\Resource;

final readonly class AddressResource
{
    public function __construct(
        public ?string $street,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
    ) {
    }
}
