<?php

namespace App\Shared\Resource;

final readonly class ContactDetailsResource
{
    public function __construct(
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
    ) {
    }
}
