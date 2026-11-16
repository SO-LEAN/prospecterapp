<?php

namespace App\Connectors\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

/**
 * A member disconnects their connection whatever its state, reconnects it by a new authorization, and pushes again
 * their exchanges the provider refused.
 */
final readonly class ConnectionLinksResource
{
    public function __construct(
        public LinkResource $disconnect,
        #[OA\Property(description: 'Ask the provider for a new authorization, while a client of Prospecter there serves the account', nullable: false)]
        public ?LinkResource $reconnect = null,
        #[OA\Property(description: 'Push again every exchange to come the provider refused, while there is one', nullable: false)]
        public ?LinkResource $retryFailed = null,
    ) {
    }
}
