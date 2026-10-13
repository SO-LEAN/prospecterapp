<?php

namespace App\Api\Resource;

final readonly class OrganizationResource
{
    public function __construct(
        public int $id,
        public ?string $corporateName,
        public ?string $form,
        public ?string $type,
        public ?string $email,
        public ?string $phoneNumber,
        public ?string $language,
        public ?AddressResource $address,
        public ?LocationResource $location,
        public ?string $observations,
        public ?ImageResource $logo,
        public ?int $holdBy,
    ) {
    }
}
