<?php

declare(strict_types=1);

namespace App\Exchanges\Resource;

use OpenApi\Attributes as OA;

/**
 * When a member acted on the exchange, and who.
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
