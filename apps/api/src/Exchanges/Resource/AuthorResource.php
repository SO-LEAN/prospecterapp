<?php

namespace App\Exchanges\Resource;

use App\PersonalData\Resource\PersonNameResource;
use OpenApi\Attributes as OA;

/**
 * The member who did something to the exchange, as their profile names them.
 */
final readonly class AuthorResource
{
    public function __construct(
        public PersonNameResource $name,
        #[OA\Property(description: 'Their email, to name them while their profile has no name')]
        public ?string $email,
    ) {
    }
}
