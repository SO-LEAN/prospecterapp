<?php

namespace App\Exchanges\Resource;

use OpenApi\Attributes as OA;

/**
 * When a member did something to the exchange, and who.
 */
final readonly class StampResource
{
    public function __construct(
        #[OA\Property(format: 'date-time')]
        public string $at,
        public AuthorResource $by,
    ) {
    }
}
