<?php

namespace App\Connectors\Resource;

use App\Shared\Resource\LinkResource;

/**
 * A member checks their connections with their providers, and connects Google by asking an authorization.
 */
final readonly class ConnectionsLinksResource
{
    public function __construct(
        public LinkResource $self,
        public LinkResource $check,
        public LinkResource $connectGoogle,
    ) {
    }
}
