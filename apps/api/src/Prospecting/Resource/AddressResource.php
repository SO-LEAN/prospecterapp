<?php

namespace App\Prospecting\Resource;

final readonly class AddressResource
{
    public function __construct(
        public ?string $street,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
    ) {
    }

    public static function orNone(?string $street, ?string $postalCode, ?string $city, ?string $country): ?self
    {
        return null === $street && null === $postalCode && null === $city && null === $country ? null : new self($street, $postalCode, $city, $country);
    }
}
