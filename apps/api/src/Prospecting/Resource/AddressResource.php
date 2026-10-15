<?php

namespace App\Prospecting\Resource;

use Prospecter\Core\Prospecting\Domain\Address;

final readonly class AddressResource
{
    public function __construct(
        public ?string $street,
        public ?string $postalCode,
        public ?string $city,
        public ?string $country,
    ) {
    }

    public static function from(?Address $address): ?self
    {
        return null === $address ? null : new self($address->street, $address->postalCode, $address->city, $address->country);
    }
}
