<?php

namespace App\Connectors\Resource;

use App\Shared\Resource\LinkResource;
use OpenApi\Attributes as OA;

/**
 * A member checks their connections with their providers, and connects Google by asking an authorization; an
 * administrator configures the client of Google of the account.
 */
final readonly class ConnectionsLinksResource
{
    public function __construct(
        public LinkResource $self,
        public LinkResource $check,
        #[OA\Property(description: 'Ask Google for an authorization, while a client of Prospecter at Google serves the account', nullable: false)]
        public ?LinkResource $connectGoogle = null,
        #[OA\Property(description: 'The client of Google of the account, for an administrator', nullable: false)]
        public ?LinkResource $googleClient = null,
    ) {
    }
}
