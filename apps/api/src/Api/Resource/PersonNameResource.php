<?php

namespace App\Api\Resource;

use Prospecter\Core\Domain\User\PersonName;

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
