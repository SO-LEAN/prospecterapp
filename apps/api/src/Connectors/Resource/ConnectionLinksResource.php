<?php

namespace App\Connectors\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

/**
 * A member disconnects their connection whatever its state, and reconnects it by a new authorization.
 */
final readonly class ConnectionLinksResource
{
    public function __construct(
        public LinkResource $disconnect,
        #[OA\Property(description: 'Ask the provider for a new authorization, while a client of Prospecter there serves the account', nullable: false)]
        public ?LinkResource $reconnect = null,
    ) {
    }
}
