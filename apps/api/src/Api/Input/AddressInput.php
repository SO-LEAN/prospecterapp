<?php

namespace App\Api\Input;

use Prospecter\Core\Domain\Organization\Address;
use Symfony\Component\Validator\Constraints as Assert;

final readonly class AddressInput
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $street = null,
        #[Assert\Length(max: 255)]
        public ?string $postalCode = null,
        #[Assert\Length(max: 255)]
        public ?string $city = null,
        #[Assert\Country]
        public ?string $country = null,
    ) {
    }

    public function toAddress(): ?Address
    {
        $address = new Address($this->street, $this->postalCode, $this->city, $this->country);

        return $address->isEmpty() ? null : $address;
    }
}
