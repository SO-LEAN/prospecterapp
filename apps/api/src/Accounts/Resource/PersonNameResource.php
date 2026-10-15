<?php

namespace App\Accounts\Resource;

use Prospecter\Core\Accounts\Domain\PersonName;

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
