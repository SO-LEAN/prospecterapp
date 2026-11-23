<?php

declare(strict_types=1);

namespace App\Exchanges\Resource;

use App\PersonalData\Resource\PersonNameResource;
use OpenApi\Attributes as OA;

/**
 * The member who acted on the exchange, named as their profile names them.
 */
final readonly class AuthorResource
{
    public function __construct(
        public PersonNameResource $name,
        #[OA\Property(description: 'Their email, which names them while their profile has no name')]
        public ?string $email,
    ) {
    }
}
