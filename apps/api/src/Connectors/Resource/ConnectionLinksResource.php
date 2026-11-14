<?php

namespace App\Connectors\Resource;

use App\Shared\Resource\LinkResource;

/**
 * A member disconnects their connection whatever its state, and reconnects it by a new authorization.
 */
final readonly class ConnectionLinksResource
{
    public function __construct(
        public LinkResource $disconnect,
        public LinkResource $reconnect,
    ) {
    }
}
