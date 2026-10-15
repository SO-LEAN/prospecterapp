<?php

namespace App\Me\Resource;

use Prospecter\Core\PersonalData\Domain\PersonName;

final readonly class PersonNameResource
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
    ) {
    }

    public static function from(PersonName $name): self
    {
        return new self($name->firstName, $name->lastName);
    }
}
