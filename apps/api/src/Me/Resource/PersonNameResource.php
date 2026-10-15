<?php

namespace App\Me\Resource;

final readonly class PersonNameResource
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
    ) {
    }
}
