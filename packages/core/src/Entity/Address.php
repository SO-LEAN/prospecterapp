<?php

declare(strict_types=1);

namespace Prospecter\Core\Entity;

final readonly class Address implements \Stringable
{
    public function __construct(
        public ?string $street = null,
        public ?string $postalCode = null,
        public ?string $city = null,
        public ?string $country = null,
    ) {
    }

    public function isEmpty(): bool
    {
        return null === $this->street && null === $this->postalCode && null === $this->city && null === $this->country;
    }

    public function __toString(): string
    {
        return implode(' ', array_filter([$this->street, $this->postalCode, $this->city, $this->country], static fn (?string $part): bool => null !== $part));
    }
}
