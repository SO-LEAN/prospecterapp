<?php

namespace App\Connectors\Resource;

use OpenApi\Attributes as OA;

/**
 * An authorization asked of Google: the browser of the member goes to its address, Google bringing it back to the
 * SPA with a code and the state of the request, for ten minutes.
 */
final readonly class AuthorizationResource
{
    public function __construct(
        #[OA\Property(format: 'uri')]
        public string $address,
    ) {
    }
}
