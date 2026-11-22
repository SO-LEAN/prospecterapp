<?php

declare(strict_types=1);

namespace App\PersonalData\Resource;

final readonly class PersonNameResource
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
    ) {
    }
}
