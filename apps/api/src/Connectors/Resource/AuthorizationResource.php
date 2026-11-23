<?php

declare(strict_types=1);

namespace App\Connectors\Resource;

use OpenApi\Attributes as OA;

/**
 * An authorization requested from Google. The member's browser goes to its address. Google brings the browser back to
 * the SPA with a code and the request's state. The authorization lasts ten minutes.
 */
final readonly class AuthorizationResource
{
    public function __construct(
        #[OA\Property(format: 'uri')]
        public string $address,
    ) {
    }
}
