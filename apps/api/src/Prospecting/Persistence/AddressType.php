<?php

namespace App\Prospecting\Persistence;

use App\Shared\Persistence\ValueObjectJsonType;
use Prospecter\Core\Prospecting\Domain\Address;

/**
 * @extends ValueObjectJsonType<Address>
 */
final class AddressType extends ValueObjectJsonType
{
    protected function toArray(object $value): array
    {
        return ['street' => $value->street, 'postalCode' => $value->postalCode, 'city' => $value->city, 'country' => $value->country];
    }

    protected function fromArray(array $data): Address
    {
        return new Address($data['street'], $data['postalCode'], $data['city'], $data['country']);
    }
}
