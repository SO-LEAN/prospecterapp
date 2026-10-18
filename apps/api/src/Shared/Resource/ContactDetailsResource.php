<?php

namespace App\Shared\Resource;

use OpenApi\Attributes as OA;

final readonly class ContactDetailsResource
{
    public function __construct(
        public ?string $email,
        #[OA\Property(description: 'In the international format', example: '+33 3 88 11 22 33')]
        public ?string $phoneNumber,
        public ?string $language,
    ) {
    }
}
