<?php

namespace App\Api\Resource;

final readonly class AccountResource
{
    public function __construct(
        public string $userName,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public ?ImageResource $picture,
        public AccountOrganizationResource $organization,
    ) {
    }
}
