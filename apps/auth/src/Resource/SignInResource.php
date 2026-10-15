<?php

namespace App\Resource;

use OpenApi\Attributes as OA;

#[OA\Schema(required: ['resume'])]
final readonly class SignInResource
{
    public function __construct(
        #[OA\Property(description: 'The authorization request the sign-in interrupted, to open again; null when the SPA has to start one')]
        public ?string $resume,
    ) {
    }
}
