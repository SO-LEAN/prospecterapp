<?php

namespace App\Connectors\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

/**
 * A member checks their connections with their providers, and connects Google by asking an authorization.
 */
final readonly class ConnectionsLinksResource
{
    public function __construct(
        public LinkResource $self,
        public LinkResource $check,
        #[OA\Property(description: 'Ask Google for an authorization, while a client of Prospecter at Google serves the account', nullable: false)]
        public ?LinkResource $connectGoogle = null,
    ) {
    }
}
